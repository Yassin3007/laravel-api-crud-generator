<?php

namespace Yassin\LaravelApiCrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class CrudGenerateCommand extends Command
{
    protected $signature = 'crud:generate {name} {--fields=} {--relations=} {--force}';

    protected $description = 'Generate a complete CRUD API for a model';

    public function handle()
    {
        $name = $this->argument('name');
        $fields = $this->option('fields');
        $relations = $this->option('relations');
        $force = $this->option('force');

        $this->info("Generating CRUD for: {$name}");

        // Parse fields
        $parsedFields = $this->parseFields($fields);
        $parsedRelations = $this->parseRelations($relations);

        // Generate components
        $this->generateMigration($name, $parsedFields, $parsedRelations);
        $this->generateModel($name, $parsedFields, $parsedRelations);
        $this->generateController($name);
        $this->generateRequests($name, $parsedFields);
        $this->generateResource($name);
        $this->generateRoutes($name);
        $this->generateFactory($name, $parsedFields);
        $this->generateSeeder($name);
        $this->generateTest($name);

        $this->info("✅ CRUD generated successfully for {$name}!");
        $this->info("📝 Don't forget to run: php artisan migrate");
        $this->info("🔗 Add the routes to your api.php if not using automatic registration");
    }

    private function parseFields($fields)
    {
        if (!$fields) return [];

        $parsed = [];
        $fieldPairs = explode(',', $fields);

        foreach ($fieldPairs as $pair) {
            $parts = explode(':', trim($pair));
            $fieldName = $parts[0];
            $fieldType = $parts[1] ?? 'string';
            $nullable = isset($parts[2]) && $parts[2] === 'nullable';

            $parsed[] = [
                'name' => $fieldName,
                'type' => $fieldType,
                'nullable' => $nullable
            ];
        }

        return $parsed;
    }

    private function parseRelations($relations)
    {
        if (!$relations) return [];

        $parsed = [];
        $relationPairs = explode(',', $relations);

        foreach ($relationPairs as $pair) {
            $parts = explode(':', trim($pair));
            $relationType = $parts[0]; // belongsTo, hasMany, etc.
            $relationModel = $parts[1];

            $parsed[] = [
                'type' => $relationType,
                'model' => $relationModel
            ];
        }

        return $parsed;
    }

    private function generateMigration($name, $fields, $relations)
    {
        $tableName = Str::snake(Str::plural($name));
        $className = 'Create' . Str::plural($name) . 'Table';
        $timestamp = date('Y_m_d_His');

        $migrationContent = $this->getMigrationStub($className, $tableName, $fields, $relations);

        $filename = database_path("migrations/{$timestamp}_create_{$tableName}_table.php");
        File::put($filename, $migrationContent);

        $this->info("✅ Migration created: {$filename}");
    }

    private function generateModel($name, $fields, $relations)
    {
        $modelContent = $this->getModelStub($name, $fields, $relations);
        $filename = app_path("Models/{$name}.php");

        File::put($filename, $modelContent);
        $this->info("✅ Model created: {$filename}");
    }

    private function generateController($name)
    {
        $controllerContent = $this->getControllerStub($name);
        $filename = app_path("Http/Controllers/Api/{$name}Controller.php");

        if (!File::exists(dirname($filename))) {
            File::makeDirectory(dirname($filename), 0755, true);
        }

        File::put($filename, $controllerContent);
        $this->info("✅ Controller created: {$filename}");
    }

    private function generateRequests($name, $fields)
    {
        $storeRequestContent = $this->getStoreRequestStub($name, $fields);
        $updateRequestContent = $this->getUpdateRequestStub($name, $fields);

        $requestDir = app_path("Http/Requests/{$name}");
        if (!File::exists($requestDir)) {
            File::makeDirectory($requestDir, 0755, true);
        }

        File::put("{$requestDir}/Store{$name}Request.php", $storeRequestContent);
        File::put("{$requestDir}/Update{$name}Request.php", $updateRequestContent);

        $this->info("✅ Requests created");
    }

    private function generateResource($name)
    {
        $resourceContent = $this->getResourceStub($name);
        $filename = app_path("Http/Resources/{$name}Resource.php");

        if (!File::exists(dirname($filename))) {
            File::makeDirectory(dirname($filename), 0755, true);
        }

        File::put($filename, $resourceContent);
        $this->info("✅ Resource created: {$filename}");
    }

    private function generateRoutes($name)
    {
        $routeContent = $this->getRouteStub($name);
        $filename = base_path("routes/api/{$name}.php");

        if (!File::exists(dirname($filename))) {
            File::makeDirectory(dirname($filename), 0755, true);
        }

        File::put($filename, $routeContent);
        $this->info("✅ Routes created: {$filename}");
    }

    private function generateFactory($name, $fields)
    {
        $factoryContent = $this->getFactoryStub($name, $fields);
        $filename = database_path("factories/{$name}Factory.php");

        File::put($filename, $factoryContent);
        $this->info("✅ Factory created: {$filename}");
    }

    private function generateSeeder($name)
    {
        $seederContent = $this->getSeederStub($name);
        $filename = database_path("seeders/{$name}Seeder.php");

        File::put($filename, $seederContent);
        $this->info("✅ Seeder created: {$filename}");
    }

    private function generateTest($name)
    {
        $testContent = $this->getTestStub($name);
        $testDir = base_path("tests/Feature/{$name}");

        if (!File::exists($testDir)) {
            File::makeDirectory($testDir, 0755, true);
        }

        File::put("{$testDir}/{$name}ApiTest.php", $testContent);
        $this->info("✅ Test created");
    }

    // Stub methods
    private function getMigrationStub($className, $tableName, $fields, $relations)
    {
        $fieldsCode = '';
        foreach ($fields as $field) {
            $nullable = $field['nullable'] ? '->nullable()' : '';
            $fieldsCode .= "            \$table->{$field['type']}('{$field['name']}'){$nullable};\n";
        }

        // Add foreign keys for belongsTo relations
        foreach ($relations as $relation) {
            if ($relation['type'] === 'belongsTo') {
                $foreignKey = Str::snake($relation['model']) . '_id';
                $fieldsCode .= "            \$table->foreignId('{$foreignKey}')->constrained()->onDelete('cascade');\n";
            }
        }

        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
{$fieldsCode}            \$table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('{$tableName}');
    }
};";
    }

    private function getModelStub($name, $fields, $relations)
    {
        $fillable = collect($fields)->pluck('name')->map(fn($field) => "'{$field}'")->join(', ');

        $relationsCode = '';
        foreach ($relations as $relation) {
            $method = Str::camel($relation['model']);
            if ($relation['type'] === 'belongsTo') {
                $relationsCode .= "\n    public function {$method}()\n    {\n        return \$this->belongsTo({$relation['model']}::class);\n    }\n";
            } elseif ($relation['type'] === 'hasMany') {
                $method = Str::plural($method);
                $relationsCode .= "\n    public function {$method}()\n    {\n        return \$this->hasMany({$relation['model']}::class);\n    }\n";
            }
        }

        return "<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class {$name} extends Model
{
    use HasFactory;

    protected \$fillable = [{$fillable}];

    protected \$casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
{$relationsCode}
}";
    }

    private function getControllerStub($name)
    {
        $variable = Str::camel($name);
        $pluralVariable = Str::plural($variable);

        return "<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\\{$name};
use App\Http\Requests\\{$name}\\Store{$name}Request;
use App\Http\Requests\\{$name}\\Update{$name}Request;
use App\Http\Resources\\{$name}Resource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class {$name}Controller extends Controller
{
    public function index(Request \$request): JsonResponse
    {
        \${$pluralVariable} = {$name}::query()
            ->when(\$request->search, function (\$query, \$search) {
                \$query->where('name', 'like', \"%{\$search}%\");
            })
            ->when(\$request->sort_by, function (\$query, \$sortBy) {
                \$direction = \$request->sort_direction ?? 'asc';
                \$query->orderBy(\$sortBy, \$direction);
            })
            ->paginate(\$request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => {$name}Resource::collection(\${$pluralVariable}->items()),
            'meta' => [
                'current_page' => \${$pluralVariable}->currentPage(),
                'last_page' => \${$pluralVariable}->lastPage(),
                'per_page' => \${$pluralVariable}->perPage(),
                'total' => \${$pluralVariable}->total(),
            ]
        ]);
    }

    public function store(Store{$name}Request \$request): JsonResponse
    {
        \${$variable} = {$name}::create(\$request->validated());

        return response()->json([
            'success' => true,
            'message' => '{$name} created successfully',
            'data' => new {$name}Resource(\${$variable})
        ], 201);
    }

    public function show({$name} \${$variable}): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new {$name}Resource(\${$variable})
        ]);
    }

    public function update(Update{$name}Request \$request, {$name} \${$variable}): JsonResponse
    {
        \${$variable}->update(\$request->validated());

        return response()->json([
            'success' => true,
            'message' => '{$name} updated successfully',
            'data' => new {$name}Resource(\${$variable})
        ]);
    }

    public function destroy({$name} \${$variable}): JsonResponse
    {
        \${$variable}->delete();

        return response()->json([
            'success' => true,
            'message' => '{$name} deleted successfully'
        ]);
    }
}";
    }

    private function getStoreRequestStub($name, $fields)
    {
        $rules = [];
        foreach ($fields as $field) {
            $rule = $this->getValidationRule($field['type']);
            if (!$field['nullable']) {
                $rule = 'required|' . $rule;
            } else {
                $rule = 'nullable|' . $rule;
            }
            $rules[] = "        '{$field['name']}' => '{$rule}',";
        }
        $rulesCode = implode("\n", $rules);

        return "<?php

namespace App\Http\Requests\\{$name};

use Illuminate\Foundation\Http\FormRequest;

class Store{$name}Request extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
{$rulesCode}
        ];
    }

    public function messages()
    {
        return [
            // Add custom messages here
        ];
    }
}";
    }

    private function getUpdateRequestStub($name, $fields)
    {
        $rules = [];
        foreach ($fields as $field) {
            $rule = $this->getValidationRule($field['type']);
            $rule = 'sometimes|' . $rule;
            $rules[] = "        '{$field['name']}' => '{$rule}',";
        }
        $rulesCode = implode("\n", $rules);

        return "<?php

namespace App\Http\Requests\\{$name};

use Illuminate\Foundation\Http\FormRequest;

class Update{$name}Request extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
{$rulesCode}
        ];
    }
}";
    }

    private function getResourceStub($name)
    {
        return "<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class {$name}Resource extends JsonResource
{
    public function toArray(\$request)
    {
        return [
            'id' => \$this->id,
            // Add your fields here
            'created_at' => \$this->created_at,
            'updated_at' => \$this->updated_at,
        ];
    }
}";
    }

    private function getRouteStub($name)
    {
        $variable = Str::kebab(Str::plural($name));

        return "<?php

use App\Http\Controllers\Api\\{$name}Controller;
use Illuminate\Support\Facades\Route;

Route::apiResource('{$variable}', {$name}Controller::class);

// Additional routes
// Route::get('{$variable}/search', [{$name}Controller::class, 'search']);
// Route::post('{$variable}/bulk-delete', [{$name}Controller::class, 'bulkDelete']);";
    }

    private function getFactoryStub($name, $fields)
    {
        $fakeData = [];
        foreach ($fields as $field) {
            $fake = $this->getFakeData($field['type'], $field['name']);
            $fakeData[] = "        '{$field['name']}' => {$fake},";
        }
        $fakeDataCode = implode("\n", $fakeData);

        return "<?php

namespace Database\Factories;

use App\Models\\{$name};
use Illuminate\Database\Eloquent\Factories\Factory;

class {$name}Factory extends Factory
{
    protected \$model = {$name}::class;

    public function definition()
    {
        return [
{$fakeDataCode}
        ];
    }
}";
    }

    private function getSeederStub($name)
    {
        return "<?php

namespace Database\Seeders;

use App\Models\\{$name};
use Illuminate\Database\Seeder;

class {$name}Seeder extends Seeder
{
    public function run()
    {
        {$name}::factory()->count(50)->create();
    }
}";
    }

    private function getTestStub($name)
    {
        $variable = Str::camel($name);
        $endpoint = Str::kebab(Str::plural($name));

        return "<?php

namespace Tests\Feature\\{$name};

use App\Models\\{$name};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {$name}ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_{$variable}s()
    {
        {$name}::factory()->count(5)->create();

        \$response = \$this->getJson('/api/{$endpoint}');

        \$response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => ['id']
                    ],
                    'meta'
                ]);
    }

    public function test_can_create_{$variable}()
    {
        \$data = {$name}::factory()->make()->toArray();

        \$response = \$this->postJson('/api/{$endpoint}', \$data);

        \$response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => ['id']
                ]);

        \$this->assertDatabaseHas('{$endpoint}', \$data);
    }

    public function test_can_show_{$variable}()
    {
        \${$variable} = {$name}::factory()->create();

        \$response = \$this->getJson(\"/api/{$endpoint}/{\${$variable}->id}\");

        \$response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => ['id']
                ]);
    }

    public function test_can_update_{$variable}()
    {
        \${$variable} = {$name}::factory()->create();
        \$updateData = {$name}::factory()->make()->toArray();

        \$response = \$this->putJson(\"/api/{$endpoint}/{\${$variable}->id}\", \$updateData);

        \$response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => ['id']
                ]);
    }

    public function test_can_delete_{$variable}()
    {
        \${$variable} = {$name}::factory()->create();

        \$response = \$this->deleteJson(\"/api/{$endpoint}/{\${$variable}->id}\");

        \$response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '{$name} deleted successfully'
                ]);

        \$this->assertDatabaseMissing('{$endpoint}', ['id' => \${$variable}->id]);
    }
}";
    }

    private function getValidationRule($type)
    {
        return match($type) {
            'string' => 'string|max:255',
            'text' => 'string',
            'integer' => 'integer',
            'boolean' => 'boolean',
            'date' => 'date',
            'email' => 'email',
            'json' => 'json',
            'decimal' => 'numeric',
            'float' => 'numeric',
            default => 'string'
        };
    }

    private function getFakeData($type, $name)
    {
        if (str_contains($name, 'email')) {
            return 'fake()->email()';
        }
        if (str_contains($name, 'phone')) {
            return 'fake()->phoneNumber()';
        }
        if (str_contains($name, 'name')) {
            return 'fake()->name()';
        }

        return match($type) {
            'string' => 'fake()->word()',
            'text' => 'fake()->paragraph()',
            'integer' => 'fake()->numberBetween(1, 100)',
            'boolean' => 'fake()->boolean()',
            'date' => 'fake()->date()',
            'email' => 'fake()->email()',
            'decimal', 'float' => 'fake()->randomFloat(2, 0, 1000)',
            default => 'fake()->word()'
        };
    }
}
