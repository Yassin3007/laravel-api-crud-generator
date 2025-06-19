# Laravel API CRUD Generator

A powerful Laravel package that generates complete CRUD APIs with a single command. This package creates everything you need for a fully functional REST API including models, controllers, requests, resources, migrations, factories, seeders, tests, and routes.

## 🚀 Features

- **Complete CRUD Generation**: Models, Controllers, Requests, Resources, Migrations, Factories, Seeders, Tests, and Routes
- **Field Type Support**: String, text, integer, boolean, date, email, JSON, decimal, float, and more
- **Relationship Support**: BelongsTo and HasMany relationships
- **Validation**: Automatic validation rules generation based on field types
- **API Resources**: JSON API responses with proper formatting
- **Pagination**: Built-in pagination support with meta information
- **Search & Filtering**: Search functionality and sorting options
- **Factory & Seeders**: Automatic fake data generation for testing
- **Feature Tests**: Complete test suite for all CRUD operations
- **Smart Fake Data**: Context-aware fake data generation (emails, names, phones, etc.)

## 📦 Installation

Install the package via Composer:

```bash
composer require yassin-ahmed/laravel-api-crud-generator
```

Publish the package configuration (optional):

```bash
php artisan vendor:publish --provider="Yassin\LaravelApiCrudGenerator\ServiceProvider"
```

## 🎯 Quick Start

Generate a complete CRUD API with a single command:

```bash
php artisan crud:generate Post --fields="title:string,content:text,published:boolean,published_at:date:nullable"
```

This creates:
- Migration file
- Post model
- PostController (API)
- Store/Update request classes
- PostResource
- API routes
- PostFactory
- PostSeeder
- Feature tests

## 📝 Usage

### Basic Usage

```bash
# Simple model with basic fields
php artisan crud:generate Product --fields="name:string,price:decimal,description:text"

# With nullable fields
php artisan crud:generate User --fields="name:string,email:email,phone:string:nullable,bio:text:nullable"

# With relationships
php artisan crud:generate Post --fields="title:string,content:text" --relations="belongsTo:User,hasMany:Comment"
```

### Command Options

| Option | Description | Example |
|--------|-------------|---------|
| `--fields` | Define model fields with types | `--fields="name:string,age:integer"` |
| `--relations` | Define model relationships | `--relations="belongsTo:User,hasMany:Post"` |
| `--force` | Overwrite existing files | `--force` |

### Field Types

| Type | Description | Validation Rule |
|------|-------------|-----------------|
| `string` | Short text (255 chars) | `string\|max:255` |
| `text` | Long text | `string` |
| `integer` | Whole numbers | `integer` |
| `boolean` | True/false values | `boolean` |
| `date` | Date values | `date` |
| `email` | Email addresses | `email` |
| `json` | JSON data | `json` |
| `decimal` | Decimal numbers | `numeric` |
| `float` | Floating point numbers | `numeric` |

### Relationship Types

| Type | Description | Example |
|------|-------------|---------|
| `belongsTo` | Many-to-one relationship | `belongsTo:User` |
| `hasMany` | One-to-many relationship | `hasMany:Comment` |

## 📚 Examples

### E-commerce Product

```bash
php artisan crud:generate Product \
  --fields="name:string,description:text,price:decimal,stock:integer,is_active:boolean,category_id:integer" \
  --relations="belongsTo:Category,hasMany:OrderItem"
```

### Blog System

```bash
# Create Category
php artisan crud:generate Category --fields="name:string,slug:string,description:text:nullable"

# Create Post with relationship
php artisan crud:generate Post \
  --fields="title:string,slug:string,content:text,excerpt:text:nullable,published_at:date:nullable,is_published:boolean" \
  --relations="belongsTo:User,belongsTo:Category,hasMany:Comment"

# Create Comment
php artisan crud:generate Comment \
  --fields="content:text,author_name:string,author_email:email" \
  --relations="belongsTo:Post"
```

### User Management

```bash
php artisan crud:generate Profile \
  --fields="first_name:string,last_name:string,bio:text:nullable,avatar:string:nullable,phone:string:nullable,date_of_birth:date:nullable" \
  --relations="belongsTo:User"
```

## 🔧 Generated Files Structure

After running the command, the following files are created:

```
app/
├── Models/
│   └── YourModel.php
├── Http/
│   ├── Controllers/Api/
│   │   └── YourModelController.php
│   ├── Requests/YourModel/
│   │   ├── StoreYourModelRequest.php
│   │   └── UpdateYourModelRequest.php
│   └── Resources/
│       └── YourModelResource.php
database/
├── migrations/
│   └── xxxx_xx_xx_xxxxxx_create_your_models_table.php
├── factories/
│   └── YourModelFactory.php
└── seeders/
    └── YourModelSeeder.php
routes/api/
└── YourModel.php
tests/Feature/YourModel/
└── YourModelApiTest.php
```

## 🌐 API Endpoints

The generated controller provides these endpoints:

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/your-models` | List all records (with pagination) |
| `POST` | `/api/your-models` | Create new record |
| `GET` | `/api/your-models/{id}` | Show specific record |
| `PUT/PATCH` | `/api/your-models/{id}` | Update record |
| `DELETE` | `/api/your-models/{id}` | Delete record |

### Query Parameters

- `search` - Search in name field
- `sort_by` - Field to sort by
- `sort_direction` - Sort direction (asc/desc)
- `per_page` - Records per page (default: 15)

### Example API Calls

```bash
# List products with pagination
GET /api/products?per_page=10&page=1

# Search products
GET /api/products?search=laptop

# Sort products by price
GET /api/products?sort_by=price&sort_direction=desc

# Create product
POST /api/products
{
    "name": "Laptop",
    "price": 999.99,
    "description": "High-performance laptop"
}

# Update product
PUT /api/products/1
{
    "name": "Gaming Laptop",
    "price": 1299.99
}
```

## 🧪 Testing

The package generates comprehensive feature tests. Run them with:

```bash
# Run all tests
php artisan test

# Run specific model tests
php artisan test tests/Feature/Product/ProductApiTest.php

# Run with coverage
php artisan test --coverage
```

## 🔄 After Generation

1. **Run migrations**:
   ```bash
   php artisan migrate
   ```

2. **Register routes** (if not using automatic discovery):
   ```php
   // In routes/api.php
   require_once __DIR__ . '/api/Product.php';
   ```

3. **Run seeders** (optional):
   ```bash
   php artisan db:seed --class=ProductSeeder
   ```

4. **Customize as needed**:
    - Update validation rules in request classes
    - Modify API resource fields
    - Add custom methods to controllers
    - Enhance factory definitions

## ⚡ Advanced Features

### Custom Validation

Update the generated request classes to add custom validation:

```php
// In StoreProductRequest.php
public function rules()
{
    return [
        'name' => 'required|string|max:255|unique:products',
        'price' => 'required|numeric|min:0',
        'description' => 'nullable|string|max:1000',
    ];
}
```

### Custom API Resources

Enhance the generated resource classes:

```php
// In ProductResource.php
public function toArray($request)
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'price' => number_format($this->price, 2),
        'description' => $this->description,
        'formatted_price' => '$' . number_format($this->price, 2),
        'category' => new CategoryResource($this->whenLoaded('category')),
        'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
    ];
}
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 📞 Support

If you encounter any issues or have questions:

1. Check the [issues page](https://github.com/yassin/laravel-api-crud-generator/issues)
2. Create a new issue if your problem isn't already reported
3. Provide detailed information about your Laravel version and the command you used

## 🙏 Credits

Created by [Yassin](https://github.com/yassin)

---

Made with ❤️ for the Laravel community