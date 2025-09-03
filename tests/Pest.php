<?php

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
