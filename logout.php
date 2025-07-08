<?php
require_once 'includes/config.php';
require_once 'includes/functions.php'; // This file contains the redirect() function

session_unset();
session_destroy();

redirect(BASE_URL);