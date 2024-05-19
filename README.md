
# Prevent Deletion

A Laravel package to prevent the deletion of models with related records.

## Installation

### Method 1: Install via Packagist

1. Run the following command to require the package:

    ```sh
    composer require yousef-aman/prevent-deletion
    ```

### Method 2: Install via GitHub

1. Add the following repository to your `composer.json` file:

    ```json
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/yousef-aman/prevent-deletion.git"
        }
    ]
    ```

2. Run the following command to require the package:

    ```sh
    composer require yousef-aman/prevent-deletion
    ```

## Usage

### Step 1: Use the Trait in Your Models

Use the `PreventDeletionIfHasRelations` trait in your models. Define any specific conditions, and optionally, specify which relationships to exclude or include in the check.

#### Example: User Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDeletionIfHasRelations;

class User extends Model
{
    use PreventDeletionIfHasRelations;

    // Exclude 'comments' from the check
    protected $excludedRelations = ['comments'];

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    // Define specific conditions dynamically
    public function specificConditions(): array
    {
        return [
            [
                'condition' => $this->is_active === false,
                'message' => 'Cannot delete this user because it is not active.'
            ],
            [
                'condition' => $this->is_verified === false,
                'message' => 'Cannot delete this user because it is not verified.'
            ]
        ];
    }

    // Optional: Customize deletion message
    public function getDeletionMessage($default)
    {
        return 'Custom deletion message: ' . $default;
    }
}
```

#### Example: Blog Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDeletionIfHasRelations;

class Blog extends Model
{
    use PreventDeletionIfHasRelations;

    // No exclusions in this example
    protected $excludedRelations = [];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Define specific conditions dynamically
    public function specificConditions(): array
    {
        return [
            [
                'condition' => $this->is_published === false,
                'message' => 'Cannot delete this blog because it is not published.'
            ]
        ];
    }

    // Optional: Customize deletion message
    public function getDeletionMessage($default)
    {
        return 'Custom deletion message: ' . $default;
    }
}
```

### Step 2: Handle Deletion in Controllers

Handle the exception in your controller to provide a user-friendly message:

```php
namespace App\Http\Controllers;

use App\Models\User;
use App\Exceptions\PreventDeletionException;
use Exception;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            return redirect()->back()->with('success', 'User deleted successfully.');
        } catch (PreventDeletionException $e) {
            // Handle deletion prevention specifically
            return redirect()->back()->with('error', $e->getMessage());
        } catch (Exception $e) {
            // Handle other exceptions
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }
}
```

## Features

### 1. Custom Exception Handling

The package uses a custom `PreventDeletionException` class to handle exceptions specifically related to deletion prevention. This makes it easier to catch and handle these exceptions separately from other types of exceptions.

```php
namespace App\Exceptions;

use Exception;

class PreventDeletionException extends Exception
{
    /**
     * Create a new PreventDeletionException instance.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  \Exception|null  $previous
     * @return void
     */
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
```

### 2. Logging

The package integrates logging to record attempts to delete models with related records. This helps in debugging and monitoring.

### 3. Configurable Messages

Models can define custom messages for deletion prevention scenarios, making the package more flexible.

```php
public function getDeletionMessage($default)
{
    return $this->deletionMessage ?? $default;
}
```

### 4. Extending Configuration

The package allows for detailed control over which relations are checked by specifying `excludedRelations` and `includedRelations`.

## Contributing

Thank you for considering contributing to this package! You can contribute by opening an issue or submitting a pull request.

## License

This package is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
