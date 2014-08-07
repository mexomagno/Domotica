#include <stdio.h>
#include <stdlib.h>
#include <dirent.h>
#include <string.h>
#include <errno.h>
#include <signal.h>
#include "jsocket6.h"

#define BUF_SIZE 200
#define PATH_SIZE 80
#define VERBOSE 1
int socket_copy;

void intHandler(int signum){
	if (VERBOSE) printf("Server terminado. Adiós!\n");
	close(socket_copy);
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
		if (fd>2){
			if (VERBOSE) printf("\nCliente desconectado, con errores: %s\n", errorstring);}
		else{
			if (VERBOSE) printf("\nServidor no puede iniciarse :(\n");
			exit(1);
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
	if (VERBOSE) printf("Comprobando disponibilidad de placa Arduino... ");
	char pathout[PATH_SIZE];
	getArduinoPort(pathout);
	if (checkError(pathout[0]==0, "Error (server): Arduino no disponible (lectura)",1))
		;
	if (VERBOSE) printf("LISTO\n");
	if (VERBOSE) printf("Abriendo puerto serial arduino para lectura... "); 
	FILE *serialout;
	if (checkError((serialout=fopen(pathout,"r")) == NULL, "Error (server): No se pudo abrir el puerto serial (lectura)", 1))
		;
	/* dar tiempo al arduino para reiniciarse */
	sleep(2);
	if (VERBOSE) printf("LISTO\n");
	/***** LISTO PARA ATENDER CLIENTES *****/
	int conexion;
	char input[BUF_SIZE];
	char output[BUF_SIZE];
	char path[PATH_SIZE];
	if (VERBOSE) printf("Servidor iniciado.\n\n");
	while(1){
		/* 1: Esperar cliente */
		if (VERBOSE) printf("Esperando cliente...\n");
		conexion=j_accept(socket);
		if (VERBOSE) printf("Cliente conectado\n");
		/* 2: Ver disponibilidad del arduino */
		if (VERBOSE) printf("Comprobando disponibilidad de placa Arduino... ");
		getArduinoPort(path);
		if (checkError(path[0]==0, "Error (server): Arduino no disponible", conexion)){
			continue;
		}
		if (VERBOSE) printf("LISTO\n");
		/* 3: Atender cliente */
		/* 3.1: leer lo que envía el cliente y guardarlo en input*/
		if (VERBOSE) printf("Leyendo datos del cliente...");
		if (checkError(read(conexion, input, BUF_SIZE) <= 0, "Error (server): No leo nada!", conexion)){
			continue;
		}
		if (VERBOSE) printf("LISTO\n");
		/* 3.2: Abrir el puerto serial al arduino */
		if (VERBOSE) printf("Comunicándose con Arduino... ");
		FILE *serial;
		if (checkError((serial=fopen(path,"w")) == NULL, "Error (server): No se pudo abrir el puerto serial (¿Arduino desconectado?)", conexion)){
			continue;
		}
		/* 3.3: Enviar datos al arduino */
		int i;
		for (i = 0; input[i]!=0; ++i)
		{
			fprintf(serial, "%c", input[i]);
			usleep(1000);
		}
		fclose(serial);
		if (VERBOSE) printf("LISTO,  Datos enviados\nRespondiendo al cliente... ");
		/* 3.4: Enviar respuesta del arduino a la peticion del cliente */
		if (checkError((read(serialout, output, BUF_SIZE)) <= 0, "Error (server): No se pudo leer respuesta del arduino", conexion)){
			continue;
		}
		write(conexion, output, 10);
		if (VERBOSE) printf("Cliente atendido con éxito\n\n");
	}
	/* Posibles mejoras futuras:
		-Implementar dos sockets: el primero escucha y el segundo atiende mediante un thread*/
}