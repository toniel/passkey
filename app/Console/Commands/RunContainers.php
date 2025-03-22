<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunContainers extends Command
{
    protected $signature = 'containers:run';
    protected $description = 'Run Docker containers with loading spinners';

    // Simbol spinner yang akan dirotasi
    protected $spinners = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
    protected $containers = [
        'laracast-passkey-mysql-1',
        'laracast-passkey-laravel.test-1',
        'laracast-passkey-nginx-1',
    ];

    // Waktu simulasi untuk setiap container (detik)
    protected $simulationTimes = [0.5, 0.8, 0.3];

    // Kode warna ANSI
    protected $green = "\033[32m";
    protected $reset = "\033[0m";

    public function handle()
    {
        $totalContainers = count($this->containers);
        $completed = 0;
        $spinnerIndex = 0;

        $this->output->writeln("{$this->green}[+] Running 0/{$totalContainers}{$this->reset}");

        foreach ($this->containers as $index => $container) {
            // Mendapatkan waktu simulasi dari array
            $simulationTime = $this->simulationTimes[$index] ?? 0.5;

            // Tampilkan loading spinner
            $startTimestamp = microtime(true);
            while (microtime(true) - $startTimestamp < $simulationTime) {
                $this->output->write("\r{$this->green}[{$this->spinners[$spinnerIndex]}] Running {$completed}/{$totalContainers}{$this->reset}");
                $spinnerIndex = ($spinnerIndex + 1) % count($this->spinners);
                usleep(100000); // Sleep 100ms
            }

            // Hitung waktu eksekusi sebenarnya
            $executionTime = microtime(true) - $startTimestamp;
            $completed++;

            // Hapus baris spinner
            $this->output->write("\r\033[K");

            // Tampilkan status completed
            $this->output->writeln("{$this->green}[+] Running {$completed}/{$totalContainers}{$this->reset}");

            // Format waktu eksekusi
            $seconds = number_format($executionTime, 1);
            $timeText = "{$seconds}s";

            // Buat output dengan format yang tepat dan sejajar
            $statusText = "Started";

            // Hitung padding untuk waktu
            $terminalWidth = 80; // Sesuaikan dengan lebar terminal jika perlu
            $paddingLength = $terminalWidth - strlen(" ✓ Container " . $container . " " . $statusText) - strlen($timeText);
            $padding = str_repeat(' ', $paddingLength);

            // Tampilkan container yang selesai dengan checkmark hijau dan status sejajar
            $this->output->writeln(" {$this->green}✓{$this->reset} Container {$container} {$statusText}{$padding}{$timeText}");
        }
    }
}
