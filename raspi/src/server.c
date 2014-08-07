#include <stdio.h>
#include <stdlib.h>
#include <dirent.h>
#include <string.h>
#include <errno.h>
#include <signal.h>
#include "jsocket6.h"
#include <termios.h>
#include <fcntl.h>

#define BUF_SIZE 200
#define PATH_SIZE 80
#define VERBOSE 1
#define BAUD 9600
#define NDISP 1

typedef struct dispositivo{
	char *nombre;
	int power;
	/* inserte parametros aqui */

	unsigned char pin;
}*DISP;

/* Arreglo de dispositivos, para llevar control de estados */
DISP *dispositivos;
char *nombres[NDISP]={"LED_13"};
unsigned char pines[NDISP]={1};

void initDisp(){
	char comando[30];
	/* recorrer arreglo de dispositivos y asignar un nuevo valor */
	dispositivos = (DISP*)malloc(sizeof(DISP)*NDISP);
	int i;
	for (i=0;i<NDISP;i++){
		dispositivos[i]=(DISP)malloc(sizeof(DISP));
		dispositivos[i]->nombre=nombres[i];
		dispositivos[i]->power=0;
		dispositivos[i]->pin=pines[i];
		sprintf(comando,"rpio --setoutput %d",pines[i]);
		system(comando);
	}
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
		strcat(resp, disp->nombre);
		/* más posibles parámetros se pueden agregar acá */
		sprintf(aux_string, ".power:");
		strcat(resp, aux_string);
		sprintf(aux_string, "%d", disp->power);
		strcat(resp, aux_string);
		sprintf(aux_string, " ");
		strcat(resp, aux_string);
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
	/* interpretar el valor HARDCODED*/
	int valor=0;
	if (!strcmp(string, "ON"))
		valor=1;
	if (!strcmp(string, "OFF"))
		valor=0;
	/* actualizar dispositivo */
	switch (param){
		case 0:
			//printf("Actualizo dispositivo...\n");
			dispositivos[disp]->power=valor;
			/* prender o apagar pin indicado */
			sprintf(comando,"rpio -s %d:%d",pines[disp],valor);
			system(comando);
			printf("Actualizado.\n");
			break;
	}
}

int socket_copy;

void intHandler(int signum){
	if (VERBOSE) printf("\nServer terminado. Adiós!\n");
	close(socket_copy);
	int i;
	for (i=0;i<NDISP;i++){
		free(dispositivos[i]);
	}
	free(dispositivos);
	exit(0);
}

void *getArduinoPort(char resp[]){
	char *path="/dev/serial/by-id/";
	DIR *folder=opendir(path);
	if (folder == NULL){
		resp[0]= 0;
		return;
	}
	struct dirent *elem;
	unsigned char found=0;
	while((elem=readdir(folder))!=NULL){
		if (strstr(elem->d_name,"arduino") != NULL){
			found=1;
			break;
		}
	}
	if (found){
		/* generar nombre del directorio */
		strcpy(resp,path);
		strcat(resp,elem->d_name);
		closedir(folder);
		return;
	}else {
		closedir(folder);
		resp[0] = 0;
		return;
	}
}

int checkError(char cond, char errorstring[],int fd){
	if (cond){
		if (VERBOSE || (fd>2)) write(fd, errorstring, strlen(errorstring)+1);//"%s: %s\n", errorstring,strerror(errno));
		printf("%s\n", strerror(errno));
		if (fd>2){
			if (VERBOSE) printf("\nCliente desconectado, con errores: %s: \n", errorstring);
		}
		else{
			if (VERBOSE) printf("Se ha perdido la conexión con el Arduino\n");
		}
		return 1;
	}
	else return 0;
}

int serialReadUntil(int fd, char *buf, char until){
	char b[1];
	int i=0;
	do{
		/* leer un caracter a la vez */
		int n = read(fd, b, 1);
		if (n==-1) return -1;
		if (n==0){
			usleep(10000);
			continue;
		}
		buf[i] = b[0];
		i++;
	}while (b[0] != until);
	/* agregar eof al string y borrar el newline */
	buf[i-1]=0;
	printf("Arduino dice: '%s'\n", buf);
	return strlen(buf)+1;
}
/*
int serialWrite(int fd, const char* str){
    int len = strlen(str);
    int n,m;
    if (checkError((n = write(fd, str, len))!=len, "Error (server): No se pudo escribir correctamente al arduino", 1))
    	return -1;
    checkError((m=write(fd, "\0" ,1))== -1, "Error (server): no se pudo enviar null character", 1);
    return 0;
}*/
int serialBegin(char pathout[]){
	int serial;
	if (checkError((serial=open(pathout, O_RDWR | O_NOCTTY)) == -1, "Error (server): No se pudo abrir el puerto serial (lectura)", 1));
	/* configuración del tty para comunicarse con arduino */
	/* tiempo para que se reinicie */
	sleep(2);
	struct termios toptions;
	tcgetattr(serial, &toptions);
	/* set BAUD baud both ways */
	/***************OJO, ASIGNAR ESTO SEGUN EL VALOR DE BAUD ************/
	cfsetispeed(&toptions, B9600);
	cfsetospeed(&toptions, B9600);
	/* 8 bits, no parity, no stop bits */
	toptions.c_cflag &= ~PARENB;
	toptions.c_cflag &= ~CSTOPB;
	toptions.c_cflag &= ~CSIZE;
	toptions.c_cflag |= CS8;
	/* Canonical mode */
	toptions.c_lflag |= ICANON;
	/* commit the serial port settings */
	tcsetattr(serial, TCSANOW, &toptions);
	tcflush(serial, TCIFLUSH);
	system("./.src/init_serial.sh"); //EN EL FUTURO, CHEQUEAR QUE ESTO SE PUDO HACER CON ÉXITO
	usleep(10000);
	return serial;
}

void main(){
	if (VERBOSE) printf("Iniciando servidor domótica...\n");
	/* manejo de señal de interrupción */
	struct sigaction sa;
	sa.sa_handler = intHandler;
	if (checkError(sigaction(SIGINT, &sa, NULL)<0, "Error (server): Imposible crear signal", 1))
		exit(1);
	int port=1818;
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
	/* es necesario tener abierto el serial para lectura, sino el arduino se resetea! */
	/*if (VERBOSE) printf("Comprobando disponibilidad de placa Arduino... ");
	char pathout[PATH_SIZE];
	getArduinoPort(pathout);
	if (checkError(pathout[0]==0, "Error (server): Arduino no disponible (lectura). Cerrando,..",1))
		exit(1);
	if (VERBOSE) printf("LISTO\n");
	if (VERBOSE) printf("Abriendo puerto serial arduino para lectura... "); 
	int serial=serialBegin(pathout);
	if (VERBOSE) printf("LISTO\n");
	*/
	initDisp();
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
		/* 2: Ver disponibilidad del arduino */
		/*if (VERBOSE) printf("Comprobando disponibilidad de placa Arduino... ");
		getArduinoPort(path);
		if (checkError(path[0]==0, "Error (server): Arduino no disponible", conexion)){
			continue;
		}
		else 
		if (VERBOSE) printf("LISTO\n");*/
		/* 3: Atender cliente */
		/* 3.1: leer lo que envía el cliente y guardarlo en input*/
		if (VERBOSE) printf("Leyendo datos del cliente...");
		if (checkError((n=read(conexion, input, BUF_SIZE)) <= 0, "Error (server): No leo nada!", conexion)){
			continue;
		}
		if (VERBOSE) printf("LISTO\n");
		/* 3.2: Si el cliente envia 'getDisps' hay que enviar lista con dispositivos y sus estados */
		input[n]=0;
		if (strcmp(input,"getDisps")==0){
			getDisps(output);
			printf("output es: %s", output);
		}
		else{
			/* 3.3: Enviar datos al arduino */
			if (VERBOSE) printf("Cliente envía '%s'\n", input);
			if (VERBOSE) printf("Configurando dispositivo... ");
			/*if (serialWrite(serial, input)==-1){
				write(conexion, "Error (server): Arduino no disponible", strlen("Error (server): Arduino no disponible"));
				write(conexion, "\0", 1);
				printf("Cerrando servidor...\n"); //EN EL FUTURO, TRATAR DE RECONECTAR EL ARDUINO
				exit(1);
			}
			if (VERBOSE) printf("LISTO,  Datos enviados\nLeyendo respuesta del arduino... ");*/
			/* 3.4: Enviar respuesta del arduino a la peticion del cliente */
			/*n = serialReadUntil(serial, output, '\n');*/
			/* actualizar estado de dispositivos en arreglo de structs dispositivos*/
			updateDisps(input);
		}
		//write(conexion, "Arduino dice: ", strlen("Arduino dice: "));
		write(conexion, output, strlen(output)+1);
		if (VERBOSE) printf("\nCliente atendido con éxito\n\n");
	}
	/* Posibles mejoras futuras:
		-Implementar dos sockets: el primero escucha y el segundo atiende mediante un thread*/
}
