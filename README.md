Blackjack server
================

To run the server:

```
composer install
php console run-server
```

This runs the TCP socket server on port 8000, and the websocket server on port 8001.
To override the TCP socket and websocket ports use `--port` and `--websocket-port`. See `php console run-server --help` for details. 
