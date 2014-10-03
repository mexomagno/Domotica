#include <stdio.h>
#include <stdlib.h>
#include <dirent.h>
#include <string.h>
#include <errno.h>
#include <signal.h>
#include "jsocket6.h"
#include <termios.h>
#include <fcntl.h>
#include <time.h>
#include <pthread.h>

#define BUF_SIZE 200
#define PATH_SIZE 80
#define VERBOSE 1
#define BAUD 9600
#define NDISP 1
#define NHORAS 5 /* Distintas horas en el día en que el dispositivo puede encenderse y apagarse.

/* DESCRIPCIÓN DE PARÁMETROS :

	POWER: indica si el dispositivo debe encenderse o no.
	START_TIME: Indica a qué hora en el día el dispositivo se encenderá. Según estas horas, el sistema seteará el valor POWER automáticamente.
				Esta variable es un arreglo de NHORAS horas seteables. Cada hora es un struct que contiene horas minutos y segundos.
	STOP_TIME:	Indica a qué hora en el día el dispositivo se apagará. Su estructura es igual que la variable anterior.

*/ 
typedef struct hora{
	int h;
	int m;
	int s;
	int isset;
}Hora;
typedef struct dispositivo{
	char *nombre;
	int power;
	Hora start_time[NHORAS];
	Hora stop_time[NHORAS];
	int intervalo; /* indica qué intervalo de tiempo lo configuró para encenderse. Otros intervalos no pueden apagarlo.*/
	/* #paramdef: inserte parametros aqui */
	unsigned char pin;
}DISP;

/* Arreglo de dispositivos, para llevar control de estados */
DISP dispositivos[NDISP];	
char *nombres[NDISP]={"LED_13"};
unsigned char pines[NDISP]={2};

/*********** manejo de horas y tiempos ************/
/* timeCmp(h1,m1,s1,h2,m2,s2) compara la hora 1 con la hora 2.
	Retorna:
				0:	si h1 == h2
				-1:	si h1 < h2
				1:	si h1 > h2
*/
int timeCmp(int h1,int m1,int s1,int h2,int m2,int s2){
	return (h1 == h2 ? (m1 == m2 ? (s1 == s2 ? 0 : (s1 > s2 ? 1 : -1)) : (m1 > m2 ? 1 : -1)) : (h1 > h2 ? 1 : -1));
}
int inIntervalo(int h1,int m1,int s1,int h2,int m2,int s2){
	struct tm *taim;
	time_t tiempo;
	time(&tiempo);
	taim = localtime(&tiempo);
	int ha=taim->tm_hour,ma=taim->tm_min,sa=taim->tm_sec;
	if (timeCmp(h1,m1,s1,h2,m2,s2) == -1){
		if ((timeCmp(h1,m1,s1,ha,ma,sa)== -1) && (timeCmp(ha,ma,sa,h2,m2,s2) == -1))
			return 1;
		else
			return 0;
	}
	else if ((timeCmp(ha,ma,sa,h1,m1,s1)==1) || (timeCmp(ha,ma,sa,h2,m2,s2)==-1))
			return 1;
		else
			return 0;
}

/* demonio encargado de ver cuando hay que encender y apagar un dispositivo según la hora */
pthread_t time_daemon;
volatile int time_run;

void *timeProcess(void *arg){
	char i,j;
	Hora hora0,hora1;
	while (time_run){
		sleep(1);
		/* encender-apagar dispositivos */
		/*printf("SOY EL DEMONIOOOOO\n");*/
		char comando[BUF_SIZE];
		int aux;
		/* 1: recorrer los dispositivos */
		for (i=0;i<NDISP;i++){
			/* 2: ver si se está dentro de algún intervalo de horario fijado */
			/* cuando está apagado, debe revisar todos los intervalos configurados */
			if (dispositivos[i].power == 0){
				for (j=0;j<NHORAS;j++){
					hora0=dispositivos[i].start_time[j];
					hora1=dispositivos[i].stop_time[j];
					if (hora0.isset && hora1.isset){
						if (inIntervalo(hora0.h,hora0.m,hora0.s,hora1.h,hora1.m,hora1.s)){
							dispositivos[i].intervalo=j;
							dispositivos[i].power=1;
							sprintf(comando,"rpio -s %d:%d",dispositivos[i].pin,dispositivos[i].power);
							system(comando);
							printf("TimeDaemon: \"%s\" encendido automáticamente\n",dispositivos[i].nombre);
							break;
						}
					}
				}
			}
			/* cuando está encendido, debe esperar a que su intervalo se acabe*/
			else {
				aux = dispositivos[i].intervalo;
				hora0=dispositivos[i].start_time[aux];
				hora1=dispositivos[i].stop_time[aux];
				if (!inIntervalo(hora0.h,hora0.m,hora0.s,hora1.h,hora1.m,hora1.s)){
					dispositivos[i].intervalo=-1;
					dispositivos[i].power=0;
					sprintf(comando,"rpio -s %d:%d",dispositivos[i].pin,dispositivos[i].power);
					system(comando);
					printf("TimeDaemon: \"%s\" apagado automáticamente\n",dispositivos[i].nombre);
				}
			}
		}
	}
	return 0;
}
void hora2text(Hora *lista,char *s){//int disp, char *cual, char *s){
	char resp[BUF_SIZE],aux[BUF_SIZE];
	int i;
	Hora haux;
	sprintf(resp,"");
	for (i=0;i<NHORAS;i++){
		if (lista[i].isset){
			haux = lista[i];
			sprintf(aux,"%sh%d-%d-%d-%d",(i!=0 ? "," : ""), (i+1),haux.h,haux.m,haux.s);
		}
		else
			sprintf(aux,"%sh%d-NULL",(i!=0 ? "," : ""),(i+1));
		strcat(resp,aux);
	}
	strcpy(s,resp);
}
/* addHora(lista de horas, hora en string) agrega la hora recibida en texto a la lista de horas entregada. 
Retorna 0 en éxito y 1 en problemas. Retorna 2 si estaba llena la lista.*/
char addHora(Hora *list,char *str){
	/* Obtener números de la hora */
	char aux[BUF_SIZE];
	char *token;
	int i;
	strcpy(aux,str);
	token = strtok(aux,":");
	int h = (int)strtol(token,NULL,10);
	token = strtok(NULL,":");
	int m = (int)strtol(token,NULL,10);
	token = strtok(NULL,":");
	int s = (int)strtol(token,NULL,10);
	if ((h>=24) || (m>=60) || (s>=60) )
		return 1;
	/* falta verificar cuando el valor ingresado es incorrecto!!!!!!!!!!! */
	/* Guardar el valor en el arreglo correspondiente */
	for (i=0;i<NHORAS;i++){
		if (list[i].isset == 0){
			list[i].h=h;
			list[i].m=m;
			list[i].s=s;
			list[i].isset=1;
			printf("Nueva hora guardada: %d:%d:%d\n",h,m,s);
			break;
		}
		if (i==NHORAS-1)
			return 2;
	}
	return 0;
}

char rmHora(Hora *list,int index){
	list[index].isset=0;
	return 0;
}
/*********** FIN horas y tiempos **************/

void initDisp(){
	char comando[30];
	/* recorrer arreglo de dispositivos y asignar un nuevo valor */
	//dispositivos = (DISP*)malloc(sizeof(DISP)*NDISP);
	int i,j;
	for (i=0;i<NDISP;i++){
		//dispositivos[i]=(DISP)malloc(sizeof(DISP));
		dispositivos[i].nombre=nombres[i];
		dispositivos[i].power=0;
		//dispositivos[i].start_time=(Hora *)malloc(sizeof(Hora)*NHORAS);
		//dispositivos[i].stop_time=(Hora *)malloc(sizeof(Hora)*NHORAS);
		for (j=0;j<NHORAS;j++){
			//(dispositivos[i].start_time)[j]=(Hora)malloc(sizeof(Hora));
			(dispositivos[i].start_time)[j].h=0;
			(dispositivos[i].start_time)[j].m=0;
			(dispositivos[i].start_time)[j].s=0;
			(dispositivos[i].start_time)[j].isset=0;
		}
		/* #paramdef: Debe ir agregando parámetros acá */
		dispositivos[i].pin=pines[i];
		sprintf(comando,"rpio --setoutput %d",pines[i]);
		system(comando);
	}
	/* HARDCODED */
	/*addHora(dispositivos[0].start_time,"18-18-00\n");
	addHora(dispositivos[0].stop_time,"18-18-30\n");
	addHora(dispositivos[0].start_time,"18-19-00\n");
	addHora(dispositivos[0].stop_time,"18-19-30\n");
	addHora(dispositivos[0].START_TIMEme,"18-20-00\n");
	addHora(dispositivos[0].stop_time,"18-20-30\n");
	addHora(dispositivos[0].start_time,"18-21-00\n");
	addHora(dispositivos[0].stop_time,"18-21-30\n");
	addHora(dispositivos[0].start_time,"18-22-00\n");
	addHora(dispositivos[0].stop_time,"18-22-30\n");*/
	/* END HARDCODED */
	/*printf("Testeando inIntervalo:\n");
	printf("00:00:00, 23:59:59: %d, esperado: 1\n",inIntervalo(0,0,0,23,59,59));
	printf("23:00:00, 23:59:59: %d, esperado: 0\n",inIntervalo(23,0,0,23,59,59));
	printf("00:00:00, 00:01:00: %d, esperado: 0\n",inIntervalo(0,0,0,0,1,0));
	printf("17:00:00, 18:00:00: %d, esperado: 1\n",inIntervalo(17,0,0,18,0,0));
	printf("00:01:00, 00:00:00: %d, esperado: 1\n",inIntervalo(0,1,0,0,0,0));
	printf("01:00:00, 00:00:00: %d, esperado: 1\n",inIntervalo(1,0,0,0,0,0));
	printf("00:00:01, 00:00:01: %d, esperado: 0\n",inIntervalo(0,0,1,0,0,1));
	printf("00:01:00, 00:01:00: %d, esperado: 0\n",inIntervalo(0,1,0,0,1,0));
	printf("01:00:00, 01:00:00: %d, esperado: 0\n",inIntervalo(1,0,0,1,0,0))*/
}

void getDisps(char *resp){
	/* Retorna la información de los dispositivos en el siguiente formato:
		NOMBRE1.power:X1 NOMBRE2.power:X2 ... etcétera */
	//char resp[BUF_SIZE];
	strcpy(resp,"");
	char aux_string[BUF_SIZE];
	DISP disp;
	int i;
	for (i=0;i<NDISP;i++){
		disp=dispositivos[i];
		strcat(resp, disp.nombre);
		/* POWER */
		sprintf(aux_string, ".power:");
		strcat(resp, aux_string);
		sprintf(aux_string, "%d", disp.power);
		strcat(resp, aux_string);
		/* START_TIME */
		sprintf(aux_string, " .start_time:");
		strcat(resp, aux_string);
		hora2text(disp.start_time,aux_string);
		strcat(resp, aux_string);
		/* STOP_TIME */
		sprintf(aux_string, " .stop_time:");
		strcat(resp, aux_string);
		hora2text(disp.stop_time,aux_string);
		strcat(resp, aux_string);
		/* #paramdef: más posibles parámetros se pueden agregar acá */
	}
	//return resp;
}

void updateDisps(char *input){
	char comando[30];
	printf("output es: %s\n",input);
	/* parsear el string enviado por client. Recordar que es de la forma A-B-C con A, B, numeros y C string */
	int index=0;//indice que recorre el string
	/* obtener el dispositivo */
	int disp=0;
	while (input[index] != '-'){
		disp+=disp*10 + (input[index++]-'0');
	}
	if (VERBOSE) printf("disp=%d\n", disp);
	/* obtener parametro */
	int param=0;
	index++;
	while (input[index] != '-'){
		param+=param*10 + (input[index++]-'0');
	}
	if (VERBOSE) printf("param=%d\n",param);
	/* obtener valor */
	char string[BUF_SIZE];
	int string_index=0;
	index++;
	while (input[index] != '\0'){
		string[string_index++]=input[index++];
	}
	string[string_index]='\0';
	if (VERBOSE) printf("valor es %s\n",string);
	//int valor=0;
	/* actualizar dispositivo */
	int valor;
	switch (param){
		case 0: //power
			valor=(strcmp(string,"ON")==0 ? 1 : 0);
			dispositivos[disp].power=valor;
			/* prender o apagar pin indicado */
			sprintf(comando,"rpio -s %d:%d",pines[disp],valor);
			system(comando);
			//printf("Actualizado.\n");
			break;
		case 1: //start_time
		case 2: //stop_time
			/* string es de la forma xx:yy:zz si es para agregar, y de la forma Xn para eliminar hora n-ésima */
			if (string[0]=='X'){
				printf("Se eliminará la hora %s\n",(string+1));
				rmHora(dispositivos[disp].start_time,(int)strtol((string+1),NULL,10));
				rmHora(dispositivos[disp].stop_time,(int)strtol((string+1),NULL,10));
			}
			else
				addHora((param == 1 ? dispositivos[disp].start_time : dispositivos[disp].stop_time), string);
			break;
		/* #paramdef: interpretar el valor*/
	}
}

int socket_copy;

void intHandler(int signum){
	if (VERBOSE) printf("\nCerrando TimeDaemon...\n");
	close(socket_copy);
	/* matar demonio */
	time_run=0;
	pthread_join(time_daemon, NULL);
	int i,j;
	if (VERBOSE) printf("\nServer terminado. Adiós!\n");
	exit(0);
}
/* checkError(condición, string para mostrar ante error, fd donde escribir string) */
int checkError(char cond, char errorstring[],int fd){
	if (cond){
		if (VERBOSE || (fd>2)) write(fd, errorstring, strlen(errorstring)+1);//"%s: %s\n", errorstring,strerror(errno));
		printf("%s\n", strerror(errno));
		if (fd>2){
			if (VERBOSE) printf("\nCliente desconectado, con errores: %s: \n", errorstring);
		}
		else{
			if (VERBOSE) printf("Error al iniciar el servidor de domótica\n");
		}
		return 1;
	}
	else return 0;
}
void main(){
	if (VERBOSE) printf("Iniciando servidor domótica...\n");
	/* manejo de señal de interrupción */
	struct sigaction sa;
	sa.sa_handler = intHandler;
	if (checkError(sigaction(SIGINT, &sa, NULL)<0, "Error (server): Imposible crear signal", 1))
		exit(1);
	int port=1235;
	/* crear socket */
	int socket;
	if (VERBOSE) printf("Creando socket... ");
	if (checkError((socket=j_socket())<0, "Error (server): Imposible crear socket",1))
		exit(1);
	if (VERBOSE) printf("LISTO\n");
	socket_copy=socket;
	/* asignar puerto */
	if (VERBOSE) printf("Abriendo puerto... ");
	if (checkError((j_bind(socket,port))<0, "Error (server): Imposible abrir puerto",1))
		exit(1);
	if (VERBOSE) printf("LISTO\n");
	initDisp();
	/* crear demonio de manejo automático */
	time_run=1;
	if (checkError(pthread_create(&time_daemon,NULL,&timeProcess,NULL), "Error (server): Imposible crear demonio automático",1))
		exit(1);
	/***** LISTO PARA ATENDER CLIENTES *****/
	int conexion;//con el cliente
	char input[BUF_SIZE];
	char output[BUF_SIZE];
	char path[PATH_SIZE];
	int n;
	char lost_connection=0;
	if (VERBOSE) printf("Servidor iniciado.\n\n");
	while(1){
		/* 1: Esperar cliente */
		if (VERBOSE) printf("Esperando cliente...\n");
		conexion=j_accept(socket);
		if (VERBOSE) printf("Cliente conectado\n");
		/* 2: Atender cliente */
		/* 2.1: leer lo que envía el cliente y guardarlo en input*/
		if (VERBOSE) printf("Leyendo datos del cliente...");
		if (checkError((n=read(conexion, input, BUF_SIZE)) <= 0, "Error (server): No leo nada!", conexion)){
			continue;
		}
		if (VERBOSE) printf("LISTO\n");
		/* 2.2: Si el cliente envia 'getDisps' hay que enviar lista con dispositivos y sus estados */
		input[n]=0;
		if (strcmp(input,"getDisps")==0){
			getDisps(output);
			printf("output es: %s", output);
		}
		else{
		/* 2.3: Enviar datos al arduino */
			if (VERBOSE) printf("Cliente envía '%s'\n", input);
			if (VERBOSE) printf("Configurando dispositivo... ");
		/* 2.4: Enviar respuesta del server a la peticion del cliente */
		/* actualizar estado de dispositivos en arreglo de structs dispositivos*/
			updateDisps(input);
			strcpy(output,"OK\0");
		}
		write(conexion, output, strlen(output)+1);
		if (VERBOSE) printf("\nCliente atendido con éxito\n\n");
	}
}

/* Posibles mejoras futuras:
	-Implementar dos sockets: el primero escucha y el segundo atiende mediante un thread
	-Implementar archivo persistente que guarde configuraciones. Si el raspberry debe reiniciarse por alguna razón, 
	el archivo ayuda a recuperar las configuraciones hechas por el usuario.
	
*/