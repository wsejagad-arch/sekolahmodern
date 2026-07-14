<?php
$lines = file('logs/login_debug.log');
echo implode('', array_slice($lines, -15));
