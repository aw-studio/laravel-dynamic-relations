# Laravel Dynamic Relations

A package for attaching/detaching dynamic relations to [Elqouent Models](https://laravel.com/docs/eloquent).

## Setup

Install the package via composer:

```php
composer require aw-studio/laravel-dynamic-relations
```

Publish the migrations:

```php
php artisan vendor:publish --tag="dynamic-relations:migraitons"
```

## Usage

Just add the `HasDynamicRelations` to a Model:

```php
use Illuminate\Database\Eloquent\Model;
use AwStudio\DynamicRelations\HasDynamicRelations;

class Page extends Model
{
    use HasDynamicRelations;
}
```

And attach a relation:

```php
$page = Page::create();

$page->attach('article', $article)

dd($page->article); // Is the attached article
```

The related Model can be detached using the `detach` method:

```php
$page->detach('article', $article);
```

### Attaching A Collection

You may wish to attach a collection of models for a "many" relation. This can be achieved by passing an instance of a collection as a second parameter to the `attach` method:

```php
$page = Page::create();

$page->attach('article', collect([$article]));

dd($page->article); // A collection containing the attached article.
```
