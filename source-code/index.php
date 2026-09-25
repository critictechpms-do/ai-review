<?php

session_start();

require 'config/env.php';        // Load environment variables first
require 'config/database.php';   // This creates $conn variable
require 'app/requireOwner.php';  // Add this line
require 'views/main/layout/header.php'; 
require 'app/Router.php';
require 'routes/index.php';

// Make $conn available globally
$GLOBALS['conn'] = $conn;