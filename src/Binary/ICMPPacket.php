<?php

namespace PrettyPhp\Binary;

class ICMPPacket
{
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
            $this->checksum = $this->calculateChecksum();
        }
    }

    /**
     * Рассчитывает контрольную сумму ICMP-пакета
     */
    private function calculateChecksum(): int
    {
        // Create a copy with checksum set to 0 to avoid circular dependency
        $temp = clone $this;
        $temp->checksum = 0;

        $packetWithoutChecksum = BinarySerializer::pack($temp);

        $bitLength = strlen($packetWithoutChecksum);
        $checksum = 0;

        for ($i = 0; $i < $bitLength; $i += 2) {
            $word = ord($packetWithoutChecksum[$i]) << 8 | ord($packetWithoutChecksum[$i + 1]);
            $checksum += $word;
        }

        $checksum = ($checksum >> 16) + ($checksum & 0xFFFF);
        $checksum += ($checksum >> 16);

        return ~$checksum & 0xFFFF;
    }
}
