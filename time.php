<?php
function stravaUtcToDb(string $iso): string {
    $dt = new DateTimeImmutable($iso);
    return $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}
function stravaUtcMinus(string $iso, int $seconds): string {
    $dt = new DateTimeImmutable($iso);
    return $dt->setTimezone(new DateTimeZone('UTC'))->modify("-{$seconds} seconds")->format('Y-m-d H:i:s');
}
