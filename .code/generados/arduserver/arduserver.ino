/********** Auto-generado por makecode.sh **********/
/* Setup:	
			-Dispositivos conectados cada uno a su pin correspondiente.

	Protocolo:
			-Primera sección indica número de dispositivo
			-Segunda sección es el caracter separador "-"
			-Tercera sección es propiedad a setear
			-Cuarta sección es caracter separador "-"
			-Quinta sección, valor a setear a la propiedad.

			Así, un input es un string que se ve de la forma NN-PP-VV.
			NN = string con el número de dispositivo a configurar
			PP = string con el número de parámetro del dispositivo que se quiere setear
			VV = String con el valor que se quiere setear.

	ADVERTENCIA:
	El arduino NO valida los valores. Esto lo hace el cliente que recibe la petición del web server. 
	El arduino sólo se limita a interpretar el input. Si se entrega input incorrecto, el resultado
	es incierto.
*/


#define BUF_SIZE 200
#define BAUD 9600
/* número de dispositivos conectados */
#define NDISP 3
/* pins de los dispositivos HARDCODED */
unsigned char disp_pins[NDISP];

char in;
int index=0;
char input[BUF_SIZE];
char output[BUF_SIZE];


void procesar(char input[]){
	int index=0;//indice que recorre el string
	/* obtener el dispositivo */
	int disp=0;
	while (input[index] != '-'){
		disp+=disp*10 + (input[index++]-'0');
	}
	/* obtener parametro */
	int param=0;
	index++;
	while (input[index] != '-'){
		param+=param*10 + (input[index++]-'0');
	}
	/* obtener valor */
	char string[BUF_SIZE];
	int string_index=0;
	index++;
	while (input[index] != '\0'){
		string[string_index++]=input[index++];
	}
	string[string_index]='\0';
	/* interpretar el valor HARDCODED*/
	int valor=0;
	if (!strcmp(string, "ON"))
		valor=HIGH;
	if (!strcmp(string, "OFF"))
		valor=LOW;
	/* setear el valor pedido HARDCODED */
	if (param==0){
		digitalWrite(disp_pins[disp], valor);
	}
	/*Serial.print("Ok. Seteado disp: ");
	Serial.print(disp);
	Serial.print(", pin: ");
	Serial.print(disp_pins[disp]);
	Serial.print(", param: ");
	Serial.print(param);
	Serial.print(", con valor ");
	Serial.print(string);*/
	Serial.print("OK\n");
}
void initDisp(){
	for (int i=0;i<NDISP;i++){
		disp_pins[i]=i+2;//EN UN FUTURO, QUIZÁS ASIGNAR PINES MAPEABLES A SHIFT REGISTERS
		pinMode(disp_pins[i], OUTPUT);
	}
}
void clearBuffer(){
	while (Serial.available())
		Serial.read();
}
void setup(){
	/* inicializar dispositivos */
	initDisp();
	/* inicializar puerto serial */
	Serial.begin(BAUD);
	clearBuffer();
}
void loop(){
	if (Serial.available()){
		/* leer caracter recibido */
		in=Serial.read();
		/* agregarlo al string */
		input[index++]=in;
		/* si se lee todo el string, procesar instrucciones que contenga */
		if (in=='\0'){
			procesar(input);
			/* "Procesar(input)" envía respuesta al servidor.*/
			index=0;
		}
	}
}