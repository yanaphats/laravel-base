## Installation

```bash
composer require sandbox-dev/laravel-html
```

## Quick Example
```php
/* Form, in a blade */
{!! Form::open(['url' => 'foo/bar']) !!}
	//
{!! Form::close() !!}

/* Input, in a blade */
{!! Form::text('username', 'Admin') !!}

/* Select, in a blade */
{!! Form::select('size', ['L' => 'Large', 'S' => 'Small'], 'L') !!}


/* use class */
{!! Form::text('username', 'Admin', ['class' => 'form-control']) !!}
```