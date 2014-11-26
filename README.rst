Silex Debug
===========

The Silex Debug service provider allows you to use the wonderful Symfony
var dumper component in your Silex application.
This provider also provide an integration with the Symfony web profiler toolbar and Twig.

To install this library, run the command below and you will get the latest
version:

.. code-block:: bash

    composer require silex/debug ~2.0@dev

And enable it in your application:

.. code-block:: php

    use Silex\Provider;

    $app->register(new Provider\DebugServiceProvider(), array(
        'debug.max_items' => 250, // this is the default
        'debug.max_string_length' => -1, // this is the default
    ));

The provider depends on ``WebProfilerServiceProvider``, so you also need to enable this if that's not
already the case:

.. code-block:: php

    $app->register(new Provider\WebProfilerServiceProvider());

*Make sure to register all other required or used service providers before* ``DebugServiceProvider``.
