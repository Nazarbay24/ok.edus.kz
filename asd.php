<?php

$fd = fopen('wordlist_8_num.txt', 'w');

for ($i = 0; $i <= 99999999; $i++)
{
    fwrite($fd, str_pad($i, 8, 0, STR_PAD_LEFT)."\n");
}

fclose($fd);
