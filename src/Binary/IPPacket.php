<?php

namespace PrettyPhp\Binary;

class IPPacket
{
    public function __construct(
        #[Binary('C')] // 4 бита версии + 4 бита длины заголовка
        public int $versionAndHeaderLength,
        #[Binary('C')] // 1 байт для типа сервиса
        public int $typeOfService,
        #[Binary('n')] // 2 байта для общей длины пакета
        public int $totalLength,
        #[Binary('n')] // 2 байта для идентификатора пакета
        public int $identification,
        #[Binary('n')] // 3 бита флагов и 13 бит смещения фрагмента
        public int $flagsAndFragmentOffset,
        #[Binary('C')] // 1 байт для времени жизни (TTL)
        public int $ttl,
        #[Binary('C')] // 1 байт для протокола
        public int $protocol,
        #[Binary('n')] // 2 байта для контрольной суммы заголовка
        public int $headerChecksum = 0,
        #[Binary('N')] // 4 байта для IP-адреса источника
        public int $sourceIp = 0,
        #[Binary('N')] // 4 байта для IP-адреса назначения
        public int $destinationIp = 0,
        #[Binary('A*')] // Данные запроса (переменной длины)
        public string $data = '',
    ) {
        if ($headerChecksum === 0) {
            $this->headerChecksum = $this->calculateChecksum();
        }
    }

    /**
     * Рассчитывает контрольную сумму заголовка IP
     */
    private function calculateChecksum(): int
    {
        // Create a copy with checksum set to 0 to avoid circular dependency
        $temp = clone $this;
        $temp->headerChecksum = 0;

        $headerWithoutChecksum = BinarySerializer::pack($temp);

        $checksum = 0;
        $bitLength = strlen($headerWithoutChecksum);

        for ($i = 0; $i < $bitLength; $i += 2) {
            $word = ord($headerWithoutChecksum[$i]) << 8 | ord($headerWithoutChecksum[$i + 1]);
            $checksum += $word;
        }

        $checksum = ($checksum >> 16) + ($checksum & 0xFFFF);
        $checksum += ($checksum >> 16);

        return ~$checksum & 0xFFFF;
    }
}
