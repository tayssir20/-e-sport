<?php

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Product;
use App\Service\ProductManager;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    private function createValidProduct(): Product
    {
        $category = new Category();
        $category->setName('Électronique');

        $product = new Product();
        $product->setName('MacBook Pro');
        $product->setDescription('Ordinateur portable Apple haute performance');
        $product->setPrice(1299.99);
        $product->setStock(10);
        $product->setImage('https://example.com/macbook.jpg');
        $product->setCategory($category);

        return $product;
    }

    public function testValidProduct(): void
    {
        $manager = new ProductManager();
        $result = $manager->validate($this->createValidProduct());
        $this->assertTrue($result);
    }

    public function testProductWithEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom est obligatoire');

        $product = $this->createValidProduct();
        $product->setName('');
        (new ProductManager())->validate($product);
    }

    public function testProductWithShortName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom doit contenir au moins 3 caractères');

        $product = $this->createValidProduct();
        $product->setName('TV');
        (new ProductManager())->validate($product);
    }

    public function testProductWithEmptyDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description est obligatoire');

        $product = $this->createValidProduct();
        $product->setDescription('');
        (new ProductManager())->validate($product);
    }

    public function testProductWithShortDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description doit contenir au moins 5 caractères');

        $product = $this->createValidProduct();
        $product->setDescription('Bon');
        (new ProductManager())->validate($product);
    }

    public function testProductWithNegativePrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix doit être un nombre positif');

        $product = $this->createValidProduct();
        $product->setPrice(-50.00);
        (new ProductManager())->validate($product);
    }

    public function testProductWithZeroPrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix doit être un nombre positif');

        $product = $this->createValidProduct();
        $product->setPrice(0);
        (new ProductManager())->validate($product);
    }

    public function testProductWithNegativeStock(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le stock doit être un nombre positif');

        $product = $this->createValidProduct();
        $product->setStock(-1);
        (new ProductManager())->validate($product);
    }

    public function testProductWithInvalidImageUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Veuillez entrer une URL valide');

        $product = $this->createValidProduct();
        $product->setImage('ceci-nest-pas-une-url');
        (new ProductManager())->validate($product);
    }

    public function testProductWithEmptyImage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'image est obligatoire");

        $product = $this->createValidProduct();
        $product->setImage('');
        (new ProductManager())->validate($product);
    }

    public function testProductWithoutCategory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Veuillez choisir une catégorie');

        $product = $this->createValidProduct();
        $product->setCategory(null);
        (new ProductManager())->validate($product);
    }
}