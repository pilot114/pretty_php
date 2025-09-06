<?php

namespace PrettyPhp\Binary;

class ICMPPacket
{
    use Checksum;

    public function __construct(
        #[Binary('C')] // 1 байт для типа сообщения ICMP
        public int $type,
        #[Binary('C')] // 1 байт для кода ICMP
        public int $code,
        #[Binary('n')] // 2 байта для контрольной суммы
        public int $checksum = 0,
        #[Binary('n')] // 2 байта для идентификатора
        public int $identifier = 0,
        #[Binary('n')] // 2 байта для номера последовательности
        public int $sequenceNumber = 0,
        #[Binary('A*')] // Данные запроса (переменной длины)
        public string $data = '',
    ) {
        if ($checksum === 0) {
            $this->checksum = $this->calculate(clone $this);
        }
    }
}
