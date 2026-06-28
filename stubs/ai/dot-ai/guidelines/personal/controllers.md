# Controllers & Actions

Use the **action pattern** for every write operation. Controllers stay thin:
validate -> bind -> delegate to action -> return resource. No business logic in
the controller.

## Standard Write Method

A standard controller method is composed of:

1. A **Form Request** that validates the request.
2. **Route model binding** (when a model is needed).
3. An **Action** invoked with `$action->handle(...)`.
4. A **Resource** for the response.

```php
public function update(UpdateUserRequest $request, User $user, UpdateUser $action): UserResource
{
    $user = $action->handle($user, $request->validated());

    return new UserResource($user);
}
```

- Pass `$request->validated()` to the action, or a single argument when that is
  all the action needs.
- The action receives the bound model on update/destroy.

## Action

- One action per write operation, `final` class, single public `handle()`
  method that returns the affected model.

```php
final class UpdateUser
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user;
    }
}
```

Read operations may return a resource directly without an action.
