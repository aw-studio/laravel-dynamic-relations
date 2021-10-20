<?php

namespace Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Page;

class HasDynamicRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function tearDown(): void
    {
        Schema::drop('pages');
        Schema::drop('articles');

        parent::tearDown();
    }

    public function test_attaching_single_relationship_creates_bridge()
    {
        $article = Article::create();

        $page = Page::create();
        $page->attach('article', $article);

        $this->assertSame(1, $page->dynamicRelations()->count());
    }

    public function test_isDynamicRelationMany_method()
    {
        $article = Article::create();

        $page = Page::create();
        $page->attach('article', $article);
        $page->attach('articles', collect([$article]));

        $this->assertFalse($page->isDynamicRelationMany('article'));
        $this->assertTrue($page->isDynamicRelationMany('articles'));
    }

    public function test_getting_single_relationship()
    {
        $article = Article::create();

        $page = Page::create();
        $page->attach('article', $article);

        $this->assertInstanceOf(Relation::class, $page->article());
        $this->assertInstanceOf(Article::class, $page->article()->getResults());
    }

    public function test_getting_single_relationship_using_getter()
    {
        $article = Article::create();

        $page = Page::create();
        $page->attach('article', $article);

        $this->assertInstanceOf(Article::class, $page->article);
    }

    public function test_getting_multiple_relationships()
    {
        $article1 = Article::create();
        $article2 = Article::create();

        $page = Page::create();
        $page->attach('articles', collect([$article1, $article2]));

        $this->assertInstanceOf(Relation::class, $page->articles());
        $this->assertInstanceOf(Collection::class, $articles = $page->articles()->getResults());
        $this->assertInstanceOf(Article::class, $articles->first());
        $this->assertCount(2, $articles);
    }

    public function test_getting_multiple_relationships_using_getter()
    {
        $article1 = Article::create();
        $article2 = Article::create();

        $page = Page::create();
        $page->attach('articles', collect([$article1, $article2]));

        $this->assertInstanceOf(Collection::class, $page->articles);
        $this->assertInstanceOf(Article::class, $page->articles->first());
        $this->assertCount(2, $page->articles);
    }

    public function test_detaching_relations()
    {
        $article1 = Article::create();
        $article2 = Article::create();

        $page = Page::create();
        $page->attach('articles', collect([$article1, $article2]));

        $this->assertSame(2, $page->articles()->count());

        $page->detach('articles', $article1);

        $this->assertSame(1, $page->articles()->count());
    }

    public function test_whereHas()
    {
        $article1 = Article::create();
        $article2 = Article::create();

        $page1 = Page::create();
        $page2 = Page::create();
        $page1->attach('articles', collect([$article1, $article2]));
        $page2->attach('articles', collect([$article2]));

        $postsWithArticle1Attached = Page::whereHas('articles', fn ($q) => $q->where('articles.id', $article1->id));
        $postsWithArticle2Attached = Page::whereHas('articles', fn ($q) => $q->where('articles.id', $article2->id));

        $this->assertSame(1, $postsWithArticle1Attached->count());
        $this->assertSame(2, $postsWithArticle2Attached->count());
    }
}

class Article extends Model
{
    //
}
