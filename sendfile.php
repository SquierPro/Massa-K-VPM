<?php
error_reporting(E_ALL);

function crc16($data)
 {
  $crc=0;
  for ($k = 0; $k < strlen($data); $k++)
   {
     $acc=0;
     $temp=(($crc >> 8) << 8);
    
     for ($bits = 0; $bits < 8; $bits++)
        {
            if (($temp ^ $acc) & 0x8000) {
                    $acc = ($acc<< 1) ^ 0x1021;
                } else {
                    $acc <<= 1;
                }
                $temp <<= 1;
        }
     
      $crc = $acc ^ ($crc << 8) ^ (ord($data[$k]) & 0xFFFF);
   }
   return pack('s',$crc);
 }

 
function find_scale(){
     //Запрос о наличии подключенных весов в сети
    $HEADER="\xf8\x55\xce"; // Заголовок
    $CMD_UDP_POLL="\x00"; //запрос весов
    
    $data=$HEADER;
    $data.=pack('s', strlen($CMD_UDP_POLL));
    $data.=$CMD_UDP_POLL;
    $data.=crc16($CMD_UDP_POLL);
    
    $ip = "255.255.255.255";
    $port = 5001;

    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP); 
    socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
    socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0));
    socket_sendto($sock, $data, strlen($data), 0, $ip, $port);
    
    while(true) {
      $ret = @socket_recvfrom($sock, $buf, 20, 0, $ip, $port);
      if($ret === false) break;
    }
    
    socket_close($sock);
    
    //echo chunk_split(bin2hex($data), 2, ' ');
    return array($ip, $port);
}

function send_tcp($message, $scale, $socket, $result){
    //отправка tcp пакета
    /*$host    = "192.168.1.222";
    $port    = 5001;
    $message = "\xf8\x55\xce\x01\x00\x80\x80\x00";*/
    
    $HEADER="\xf8\x55\xce"; // Заголовок
    
    $data=$HEADER;
    
    if(strlen($message)==1) {
     $data.=pack('s', strlen($message));
    }
    $data.=$message;
    $data.=crc16($message);
    

    
    
    socket_write($socket, $data, strlen($data)) or die("Could not send data to server\n");
    $result = socket_read ($socket, 1024) or die("Could not read server response\n");
    
    //echo chunk_split(bin2hex($result), 2, ' ').'<br>';
    
    
    return $result;
}   

function generate_name(){ //Генератор названия
  $chars="1234567890QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm";
  $max=rand(2,20);
 
 // Определяем количество символов в $chars
 
 $size=StrLen($chars)-1;
 
 // Определяем пустую переменную, в которую и будем записывать символы.
 
 $password=null;
 
 // Создаём пароль.
 
     while($max--)
     $password.=$chars[rand(0,$size)];
     
     return $password;
}
/*** ТИПЫ ФАЙЛОВ ***/
$FILE_TYPE_PLU="\x01"; //Товары (PLU) - 20000 записей
$FILE_TYPE_FORMATS="\x02"; //Форматы - 10 записей
$FILE_TYPE_BARCODES="\x03"; //Штрихкоды - 20 записей
$FILE_TYPE_LOGOS="\x04"; //Логотипы - 4 записи
$FILE_TYPE_TEXTS="\x05"; //Тексты - 200 записей
$FILE_TYPE_FUNC="\x06"; //Функции клавиатуры - 100 записей
$FILE_TYPE_RES="\x07"; //Итоги - 700 записей
$FILE_TYPE_TRANS="\x08"; //Транзакции
$FILE_TYPE_FORMATLITE="\x09"; //Форматы LITE
$FILE_TYPE_STRUCTURES="\x0a"; //Структуры чека
$FILE_TYPE_OPERATORS="\x0b"; //Операторы


/*** UDP и TCP КОМАНДЫ ***/
$CMD_TCP_GET_STATUS="\x80"; //запрашивает состояние файлов
$CMD_TCP_DFILE="\x82"; //загружаем в весы запись
$CMD_TCP_BAD_DFILE="\x43"; //Ответ от весов, запись с неожидаемым номером или типом файла
$CMD_TCP_ACK_DFILE="\x42"; //загружаем в весы запись
$CMD_TCP_REQ_UFILES="\x85"; //запрос записи файла из памяти весов
$CMD_TCP_UFILE="\x45"; //весы загружают в управляющую программу часть файла из FLASH
$CMD_TCP_ERR_UFILE="\x46"; //запись файла передать невозможно
$CMD_TCP_ACK_RESET_FILES="\x41"; //команда стереть файлы выполнена
$CMD_TCP_RESET_FILES="\x81"; //удаление файлов из памяти
$CMD_TCP_FILE_STATUS="\x40"; //Какие файлы не загружены
$CMD_TCP_NACK="\xF0"; //неверный CRC
$CMD_UDP_DFILE="\x02"; //Весы в режиме on-line посылают в сеть запись


/*** ДОПОЛНИТЕЛЬНО ***/
$TERM_C = "\x0C"; // Конец строки
$TERM_D = "\x0D"; // Конец последней строки
$STATUS_PLU_VALUE="\x00"; // Центровка наименования: x01 = true; x00 = false.
$STATUS_PLU_COUNTRY="\x00"; // Код страны: 0x00 = Россия.
$FORMAT_LABEL="\x00"; // Формата этикетки: 0x00 ... 0x0A = файл формата этикеток
$FORMAT_BARCODE="\x00"; // Формат штрихкода: 0x00 ... 0x0A = файл штрихкодов
$PREFIX="\x00"; // Префикс штрихкода
$WEIGHT_CONTAINER="\x00\x00\x00\x00"; //вес тары
$PROFUCT_CODE="\x00\x00\x00\x00"; //код продукта
$DATE_REALIZATION="\x00\x00\x00\x00\x00\x00"; //дата реализации ГГ ММ ДД ЧЧ ММ СС
$DATE_VALIDITY="\x00\x00"; //срок годности в минутах
$CODE_SERTIFICATION="\x20\x20\x20\x20"; //Код органа сертификации
$NUMBER_GROUP_GENERAL="\x00\x00"; //номер основной группы
$RESERVATION="\x00\x00"; //резерв
$CODE_FONT="\x00"; //размер шрифта
$STRING_CONSISTATION = "\x00\x00".$TERM_D; // Строка состав товара: 0x00 = размер шрифта строки; 0x00 = длина строки.
$STRING_INFORMATION = "\x00\x00".$TERM_D; // Строка информационного сообщения: 0x00 = размер шрифта строки; 0x00 = длина строки.



/*** НАЧАЛО ***/
$scale=find_scale(); //Поиск весов

echo 'Find scale '.$scale[0].':'.$scale[1].'<br>';


$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
$result = socket_connect($socket, $scale[0], $scale[1]) or die("Could not connect to server\n");

$file_status=send_tcp($CMD_TCP_GET_STATUS, $scale, $socket, $result); //ответ о файлах

//ТОВАР:
for ($i = 1; $i <= 40; $i++)
   { //Добавление сорока товаров со случайным именем, ценой и сроком годности
      $names=generate_name();
     $validity=rand(0,14);
     $products[$i]=array('plu'=>$i,'name'=>$i.$names,'price'=>rand(1,500),'validity'=>$validity);
     
     echo $i.') '.$names.'<br>';
   }

$result=array();
foreach($products as $pr){
  
   $name_cp1251=iconv( "utf-8", "windows-1251",$pr['name']);
   
   $result[$pr['plu']].=pack('i', $pr['plu']); //PLU номер
   $result[$pr['plu']].=pack('s', (38+strlen($name_cp1251)+3+strlen($STRING_CONSISTATION)+strlen($STRING_INFORMATION))); //длина записи
   
   
   $result[$pr['plu']].=$STATUS_PLU_VALUE; //статус PLU
   $result[$pr['plu']].=$STATUS_PLU_COUNTRY; // Статус PLU - Код страны
   $result[$pr['plu']].=$FORMAT_LABEL; // Номер формата этикетки
   $result[$pr['plu']].=$FORMAT_BARCODE; // Номер формата штрихкода
   $result[$pr['plu']].=$PREFIX; //префикс товара
   $result[$pr['plu']].=pack('i', $pr['price']*100); //цена в копейках
   
   $result[$pr['plu']].=$WEIGHT_CONTAINER; //вес тары
   $result[$pr['plu']].=$PROFUCT_CODE; // код товара
   $result[$pr['plu']].=$DATE_REALIZATION; //дата реализации
   $result[$pr['plu']].=pack('i', $pr['validity']*1440).$DATE_VALIDITY; // срок годности
   $result[$pr['plu']].=$CODE_SERTIFICATION; //код органа сертификации
   $result[$pr['plu']].=$NUMBER_GROUP_GENERAL; //номер основной группы
   $result[$pr['plu']].=$RESERVATION; //резерв
   $result[$pr['plu']].=$CODE_FONT; //размер шрифта
   $result[$pr['plu']].=pack('c', strlen($name_cp1251)); //длина строки
   
   $array=array();
   for($i=0;$i<strlen($name_cp1251);$i++){
        $result[$pr['plu']].=hex2bin(dechex(ord($name_cp1251[$i]))); //буквы в bin
   }

   $result[$pr['plu']].=$TERM_D; //Терминатор строки
   $result[$pr['plu']].=$STRING_CONSISTATION; //состав товара
   $result[$pr['plu']].=$STRING_INFORMATION; // информационное сообщение
   
   //вычисляем контрольную сумму
   $ar  = str_split($result[$pr['plu']]);
   $ord = array_map('ord', $ar);
   $sum = array_sum($ord); // простое суммирование данных
   $crc = $sum & 0xFF;
           
   $result[$pr['plu']].=pack('c', $crc);
}

$result=implode('',$result);
$datamess=str_split($result, 1024);

$c=0;
foreach($datamess as $dm) {
 //сообщение
 $c++;
 $data=$HEADER; // Заголовочная последовательность
 $data.=pack('s', strlen($dm)+8); //длина тела
 
 $body=$CMD_TCP_DFILE; //CMD_TCP_DFILE
 $body.=$FILE_TYPE_PLU; //Тип файла PLU товары
 
 $body.=pack('s', count($datamess)); //число записей в файле - разбить запись по 1024
 $body.=pack('s', $c); //номер текущей записи
 $body.=pack('s', strlen($dm)); //длина записи
 $body.=$dm;
 
 $data.=$body.crc16($body);
//echo chunk_split(bin2hex($data), 2, ':').'<br>';

$send_file=send_tcp($data, $scale, $socket, $result);

//echo chunk_split(bin2hex($send_file), 2, ':').'<br>';

$return=substr($send_file,5, -2);

$return_cmd=substr($return,0,1);

if($return_cmd==$CMD_TCP_ACK_DFILE) {
 //Ответ от весов, запись файла загружена
 $count=substr($return,2,2);
 $num=substr($return,4,2);
 echo 'Загружен файл товаров '.unpack('s', $num)[1].'/'.unpack('s', $count)[1].'<br>';

}elseif($return_cmd==$CMD_TCP_BAD_DFILE){
 
 echo 'Ошибка: запись с неожидаемым номером или типом файла';
}

//echo chunk_split(bin2hex($CMD_TCP_ACK_DFILE), 2, ':').'<br>---';
}



$formats[]=array(
   'number'=>1, //номер формата
  'height'=>0, //30mm
  'prefix'=>44,
  'barcode'=>4, //PPТТТТTWWWWWК
);
$result=array();
$n=0;
foreach($formats as $fr){
 $n++;
  $result[$n].=pack('i', $fr['number']); //PLU номер
  $result[$n].=pack('s', 16); //длина записи
  
  $result[$n].="\x00\x00"; //статус записи
  $result[$n].=pack('c', $fr['height']); //длина этикетки
  $result[$n].=pack('c', $fr['prefix']); //префикс штрихкода
  $result[$n].=pack('c', $fr['barcode']); //номер штрихкода
  
  $result[$n].="\x00\x00\x00\x00\x00\x00\x00\x00"; //резерв
  $result[$n].=$STRING_INFORMATION; //текст рекламы
  
}
                
$result=implode('',$result);

echo chunk_split(bin2hex($result), 2, ':').'<br>---';

$datamess=str_split($result, 1024);

$c=0;
foreach($datamess as $dm) {
 //сообщение
 $c++;
 
$data=$HEADER; // Заголовочная последовательность
 $data.=pack('s', strlen($dm)); //длина тела
 
 $body=$CMD_TCP_DFILE; //CMD_TCP_DFILE
 $body.=$FILE_TYPE_FORMATLITE; //Тип файла PLU товары
 
 $body.=pack('s', count($datamess)); //число записей в файле - разбить запись по 1024
 $body.=pack('s', $c); //номер текущей записи
 $body.=pack('s', strlen($dm)); //длина записи
 $body.=$dm;
 
 $data.=$body.crc16($body);
 
echo chunk_split(bin2hex($data), 2, ':').'<br>---';
$send_file=send_tcp($data, $scale, $socket, $result);
echo chunk_split(bin2hex($send_file), 2, ':').'<br>';
}


socket_close($socket);




?>
