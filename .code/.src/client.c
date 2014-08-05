#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include "jsocket6.h"

#define BUF_SIZE 200
#define CVERBOSE 1
#define NDISP 1
#define NPARAMS 1 /* DEBE CALZAR CON LOS ELEMENTOS DEL ARREGLO params */

char *params[NPARAMS]={"power"}; /* EXTENSIBLE */
//char *power_values[]={"ON", "OFF"};
//char **values[]={power_values};

void checkError(char cond, char errorstring[]){
   if (cond){
      //printf("%s\n", strerror(errno));
      printf("Error (client): %s\n", errorstring);
      exit(1);
   }
}

// void givenames(){
//    int i;
//    for (i=0; i<NDISP; i++){
//       printf("%s ", nombres[i]);
//    }
//    printf("\n");
//    exit(0);
// }
/* isParam(param) retorna -1 si no existe el parámetro.
   Si existe, retorna subíndice del parámetro */
int isParam(char *candidato){
   int i;
   for (i=0; i<NPARAMS; i++){
      if (strcmp(params[i], candidato)==0)
         return i;
   }
   return -1;
}
/* isValue(candidato, indice) retorna 1 si el candidato es parametro valido, 0 si no. */
/* Este método está HARDCODEADO. POCO GENERAL. */
int isValue(char *candidato, int param_index){
   switch (param_index){
      case 0: //power
         if ((!strcmp(candidato, "ON")) || (!strcmp(candidato, "OFF")))
            return 1;
         break;
      default:
         return 0;
   }
   return 0;
}

/* instrucciones posibles: (getnames), (disp_id opc val) */
main(int argc, char *argv[]) {
   /* Validaciones */
   /* 1: validar cantidad de argumentos */
   checkError(!( ((argc>1)&&(strcmp(argv[1],"getDisps")==0)) || (argc>=4) ), "Faltan argumentos");
   if ((argc > 4) && (CVERBOSE)){
      printf("Exceso de argumentos. Se ignorarán los argumentos extra\n");
   }
   /* 2: validar valores */
   /* 2.0: validar si es petición de nombres de dispositivos */
   char getnames=0;
   if (strcmp(argv[1],"getDisps")==0)
      getnames=1;
   /* 2.1: validar número de dispositivo */
   int disp_id, param_index;
   char *value;
   if (!getnames){
      disp_id=strtol(argv[1],NULL,10);
      checkError((errno==EINVAL)||(errno==ERANGE), "Id de dispositivo inválida");
      checkError(disp_id>=NDISP, "No existe el dispositivo");
      /* 2.2: validar parámetro a configurar */
      char *param=argv[2];
      param_index;
      checkError((param_index=isParam(param)==-1),"No existe el parámetro para configurar");
      /* 2.3: validar valor del parametro */
      value=argv[3];
      checkError(isValue(value, param_index)==0, "Valor inválido para el parámetro");
      /* Pasó las validaciones. A partir de ahora, todos los datos son totalmente limpios */
   }
   int s;
   int cnt, n;
   char input[BUF_SIZE];
   char output[BUF_SIZE];
   /* Concatenar parámetros en un string entendible por el arduino */
   if (getnames)
      sprintf(input, "getDisps");
   else
      sprintf(input, "%d-%d-%s", disp_id, param_index, value);
   s = j_socket();

   checkError(j_connect(s, "localhost", 1818) < 0, "Servidor no responde");
   //write(a,b,c) escribe hasta c bytes en el fd a, lo que está en el buffer b. Retorna cantidad de bytes escritos.
   int cuantos = write(s, input, strlen(input) +1);
   if (CVERBOSE) printf("%d bytes enviados al servidor\n", cuantos);
   //read(a,b,c) lee desde a, hasta c bytes y los guarda en buffer b. Retorna cantidad de bytes leidos, hasta EOF (0, '\0').
   cnt=read(s, output, BUF_SIZE);
   if (CVERBOSE) printf("Respuesta del server: ");
   if (strcmp(output,"OK")==0)
      printf("OK\n");
   else
      printf("%s\n",output);
   if (CVERBOSE) printf("%d bytes leidos del servidor\n",cnt);
   close(s);
}
