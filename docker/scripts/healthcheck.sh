#!/bin/bash
set -e

# Check if Apache is running
if ! pgrep apache2 > /dev/null; then
    echo "Apache is not running"
    exit 1
fi

# Check if we can make HTTP request
if ! curl -f -s http://localhost/health > /dev/null; then
    echo "Health check endpoint failed"
    exit 1
fi

echo "Health check passed"
exit 0
