<?php

// OpenTelemetry Auto-Instrumentation Loader
// This file is auto-prepended to every PHP script to enable OpenTelemetry

if (extension_loaded('opentelemetry')) {
    // Load Composer autoloader if not already loaded
    if (!class_exists('OpenTelemetry\SDK\Sdk')) {
        $autoloadFile = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoloadFile)) {
            require_once $autoloadFile;
        }
    }
    
    // Check if the required classes are available
    if (class_exists('OpenTelemetry\Contrib\Instrumentation\Symfony\SymfonyInstrumentation')) {
        // Include the Symfony auto-instrumentation register file
        $registerFile = __DIR__ . '/../vendor/open-telemetry/opentelemetry-auto-symfony/_register.php';
        
        if (file_exists($registerFile)) {
            require_once $registerFile;
        }
    }
    
    // Ensure context propagation is enabled for FrankenPHP
    if (class_exists('OpenTelemetry\Context\Context')) {
        // Force initialization of context storage for fiber-safe operation
        \OpenTelemetry\Context\Context::storage();
    }
}
