#!/bin/bash
function stopmake(){
	echo "**** ERROR FATAL: no hay conexión con el Arduino ****"
	echo "Eliminando archivos creados..."
	rm -R "$confdir/generados" 2> /dev/null
	rm "$confdir/server" "$confdir/client" 2> /dev/null
	echo "Listo. Tarea finalizada con errores :("
	exit 1
}
conffile="domotica.config"
confdir=$(pwd)
#crear directorios y archivos de trabajo
echo "Generando directorios..."
mkdir generados
cp .src/server.c generados/
cp .src/client.c generados/
cp .src/jsocket6.h generados/
mkdir generados/arduserver
cp .src/arduserver/arduserver.ino generados/arduserver/
cd generados/
opciones=( "#define BUF_SIZE" "#define PATH_SIZE" "#define VERBOSE" "#define CVERBOSE" )
NDISP=$(grep -c "^#disp:" ../$conffile)
mensaje="/********** Auto-generado por makecode.sh **********/"
#generar server.c
echo "Creando server.c..."
sed -i "1i$mensaje" server.c
for opc in "${opciones[@]}"
do
	lineaconf=$(grep "$opc" ../$conffile)
	valor=$(echo $lineaconf | cut -d':' -f2)
	lineafile="$opc ${valor}"
	sed -i "s/^$opc.*/$lineafile/" server.c
done
#generar arreglo con los nombres de los dispositivos
arreglo="char *nombres[NDISP]={"
coma=0
while read line
do
	query=$(grep "^#disp:*" <<< $line)
	if [ "$query" != "" ]
	then
		if [ $coma == 1 ]
		then
			arreglo="$arreglo,"
		fi
		nombre=$(echo $line | cut -d':' -f2)
		arreglo="$arreglo\"$nombre\""
		coma=1
	fi
done < "../$conffile"
arreglo="$arreglo};"
sed -i "s/^char \*nombres.*/$arreglo/" server.c
sed -i "s/^#define NDISP.*/#define NDISP $NDISP/" server.c
#generar client.c
echo "Creando client.c..."
sed -i "1i$mensaje" client.c
for opc in "${opciones[@]}"
do
	lineaconf=$(grep "$opc" ../$conffile)
	valor=$(echo $lineaconf | cut -d':' -f2)
	lineafile="$opc ${valor}"
	sed -i "s/^$opc.*/$lineafile/" client.c
done
sed -i "s/^#define NDISP.*/#define NDISP $NDISP/" client.c
#generar .ino . Acá hay que agregar los dispositivos
#respectivos, definidos en el archivo de configuración.
echo "Creando arduserver.ino..."
cd arduserver/
sed -i "1i$mensaje" arduserver.ino
for opc in "${opciones[@]}"
do
	lineaconf=$(grep "$opc" ../../$conffile)
	valor=$(echo $lineaconf | cut -d':' -f2)
	lineafile="$opc ${valor}"
	sed -i "s/^$opc.*/$lineafile/" arduserver.ino
done
sed -i "s/^#define NDISP.*/#define NDISP $NDISP/" arduserver.ino
#generar makefile
cd ../
echo "Creando Makefile..."
echo "server:" > Makefile
echo -e "\tgcc server.c ../.src/jsocket6.4.c -o ../server" >> Makefile
echo "client:" >> Makefile
echo -e "\tgcc client.c ../.src/jsocket6.4.c -o ../client" >> Makefile
echo "all:" >> Makefile
echo -e "\tgcc server.c ../.src/jsocket6.4.c -o ../server" >> Makefile
echo -e "\tgcc client.c ../.src/jsocket6.4.c -o ../client" >> Makefile
echo "clean:" >> Makefile
echo -e "\trm ../server ../client ../*~ ../*.o" >> Makefile
#preparar puerto serial para comunicación con arduino
echo "Preparando puerto serial con arduino..."
path="/dev/serial/by-id/$(ls /dev/serial/by-id/ 2> /dev/null | grep arduino)"
if [ "$path" == "/dev/serial/by-id/" ]
then
	stopmake
fi
stty -F $path cs8 9600 -parenb -parodd cs8 hupcl \
-cstopb cread clocal -crtscts \
-ignbrk -brkint -ignpar -parmrk -inpck -istrip -inlcr \
-igncr -icrnl -ixon -ixoff -iuclc \
-ixany -imaxbel -iutf8 \
-opost -olcuc -ocrnl -onlcr -onocr -onlret -ofill -ofdel \
nl0 cr0 tab0 bs0 vt0 ff0 \
-isig -icanon -iexten -echo -echoe -echok -echonl noflsh \
-xcase -tostop -echoprt -echoctl -echoke
#ejecutar makefile
echo "Listo. Compilando server y cliente..."
make all
echo "Listo. Tarea finalizada con éxito :)"