= CSGT Utils =
== Docker ==
To generate the docker configuration, simply run the `php artisan make:csgtdocker` command. This will create the dockerfiles folder as well as the docker-compose files. Modify to your needs.  
If you need to disable scheduling or horizon, simply delete the corresponding sections on the supervisord.conf file.

== Bootstrap scaffolding method ==

`make:csgtscaffold`

Asks label, field name and type and generates bootstrap's form group scaffold to copy into your code, including validation classes.

[8.0]

-   New menu structure
-   New Laravel integrated php/nginx/scheduler/horizon

[7.0]

-   To use in Laravel CSGT using laravel ui

[6.0]

-   To use in Laravel CSGT using Laravel without laravel ui
