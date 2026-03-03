<?php

class phpSerial
{
    private ?string $device = null;
    private $handle = null;

    public function deviceSet(string $device): bool
    {
        $this->device = $device;

        return true;
    }

    public function confBaudRate(int $rate): bool
    {
        return true;
    }

    public function confParity(string $parity): bool
    {
        return true;
    }

    public function confCharacterLength(int $length): bool
    {
        return true;
    }

    public function confStopBits(int $stopBits): bool
    {
        return true;
    }

    public function confFlowControl(string $flowControl): bool
    {
        return true;
    }

    public function deviceOpen(string $mode = 'c+b'): bool
    {
        if ($this->handle) {
            return true;
        }

        if (!$this->device) {
            return false;
        }

        $this->handle = @fopen($this->device, $mode);

        return is_resource($this->handle);
    }

    public function deviceClose(): bool
    {
        if (!is_resource($this->handle)) {
            return true;
        }

        $result = fclose($this->handle);
        $this->handle = null;

        return $result;
    }

    public function sendMessage(string $message): int|false
    {
        if (!is_resource($this->handle)) {
            return false;
        }

        return fwrite($this->handle, $message);
    }

    public function readPort(int $count = 0)
    {
        if (!is_resource($this->handle)) {
            return false;
        }

        if ($count > 0) {
            return fread($this->handle, $count);
        }

        return stream_get_contents($this->handle);
    }

    public function __destruct()
    {
        $this->deviceClose();
    }
}
