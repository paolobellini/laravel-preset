# Query Builder

`spatie/laravel-query-builder` is used **only in read methods** — `index` and
any other `GET` that lists resources. Nowhere else.

- Never in `store` / `update` / `destroy`, never in an action, never in a
  model, never in a job or a command.
- A single-record `show` uses route model binding, not `QueryBuilder`.
- Everything the builder exposes must be declared allow-list style. There is no
  implicit filter, sort or include.

```php
public function index(IndexUserRequest $request): AnonymousResourceCollection
{
    $users = QueryBuilder::for(User::class)
        ->allowedFilters([
            AllowedFilter::partial('name'),
            AllowedFilter::exact('status'),
            AllowedFilter::scope('registered_before'),
        ])
        ->allowedSorts(['name', 'created_at'])
        ->allowedIncludes(['team', 'rolesCount'])
        ->defaultSort('-created_at')
        ->paginate()
        ->withQueryString();

    return UserResource::collection($users);
}
```

- Plain strings in `allowedFilters()` become **partial** filters — write the
  `AllowedFilter::` factory explicitly so the match type is never implicit.
- `AllowedFilter::scope()` reuses the model's `#[Scope]` local scopes; keep the
  filter logic in the model, not in a callback in the controller.
- Always `defaultSort()`. An unsorted paginated list returns rows in an
  undefined order across pages.

## Input Must Still Be Validated

`QueryBuilder` reads the query string **directly from the request**, bypassing
the Form Request entirely. The allow-list constrains *which* parameters are
accepted, never *what values* they carry — `?filter[status]=whatever` reaches
the database as-is.

So the Form Request stays mandatory, and it validates the query parameters by
their real, nested names:

```php
final class IndexUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'filter' => ['array:name,status,registered_before'],
            'filter.name' => ['string', 'min:2', 'max:150'],
            'filter.status' => ['string', Rule::enum(UserStatus::class)],
            'filter.registered_before' => ['date', 'before:today'],
            'sort' => ['string', Rule::in(['name', '-name', 'created_at', '-created_at'])],
            'include' => ['string', Rule::in(['team', 'rolesCount'])],
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', 'min:1', 'max:100'],
        ];
    }
}
```

- Type-hint the Form Request in the method signature even though the values are
  read by `QueryBuilder` — that is what makes validation run before the query.
- `array:...` on the `filter` key rejects unknown filter names with a 422
  instead of letting the package throw `InvalidFilterQuery` (a 400 with a
  message shaped by the package, not by the application).
- Rules are real and meaningful, same standard as every other Form Request —
  `min`/`max`, `Rule::enum`, `Rule::in`, `date`. Never a bare `string`.
- The allowed values in `sort` / `include` must mirror `allowedSorts()` /
  `allowedIncludes()` exactly. When one changes, the other changes with it.
