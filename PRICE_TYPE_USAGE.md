# Price Type Feature - Usage Guide

## For Property Form (Create/Edit)

### Add to Your Form View

```html
<div class="form-group">
    <label for="price_type" class="form-label">Price Type <span class="text-danger">*</span></label>
    <select name="price_type" id="price_type" class="form-select" required>
        <option value="">Select Price Type</option>
        <option value="Per Month" {{ old('price_type') === 'Per Month' ? 'selected' : '' }}>Per Month</option>
        <option value="Full Price" {{ old('price_type') === 'Full Price' ? 'selected' : '' }}>Full Price</option>
    </select>
    @error('price_type')
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>
```

## For Displaying Properties

### On Property Cards

```blade
<div class="property-price">
    <strong>₱{{ number_format($property->price) }}</strong>
    <small class="text-muted">{{ $property->price_type }}</small>
</div>
```

**Result:** ₱36,000 Per Month

### On Property Details Page

```blade
<div class="price-section">
    <h3>Price</h3>
    <p class="price-display">
        ₱{{ number_format($property->price) }} <span class="price-type">({{ $property->price_type }})</span>
    </p>
</div>
```

## In API Responses

If you're returning property data via API:

```php
return [
    'property_id' => $property->property_id,
    'property_name' => $property->property_name,
    'price' => $property->price,
    'price_type' => $property->price_type,  // Add this
    // ... other fields
];
```

## Validation Examples

### Store Request
The controller automatically validates:
- `price_type` is required
- `price_type` must be a string
- `price_type` must be one of: "Per Month" or "Full Price"

### Error Messages
If someone sends invalid `price_type`:
```
The price type field must be one of: Per Month, Full Price.
```

## Database Queries

### Get all properties by rental monthly price
```php
$monthlyRentals = Property::where('price_type', 'Per Month')->get();
```

### Get all properties for sale
```php
$forSale = Property::where('price_type', 'Full Price')->get();
```

## Filtering/Searching

### Filter properties by price type
```php
// In your repository or controller
$properties = Property::query();

if ($request->has('price_type')) {
    $properties->where('price_type', $request->price_type);
}

$results = $properties->paginate();
```

## Frontend Display Helpers

### Add to Blade helpers if needed
```php
// In a helper file or model
public function priceDisplay()
{
    return '₱' . number_format($this->price) . ' ' . $this->price_type;
}
```

Then use in views:
```blade
<p>{{ $property->priceDisplay() }}</p>
```

## Data Migration (if needed)

To bulk update existing properties:

```php
// Set all existing properties to "Full Price" (already done by default)
Property::whereNull('price_type')->update(['price_type' => 'Full Price']);

// Or update specific properties
Property::where('property_type', 'Condo')->update(['price_type' => 'Per Month']);
```

## Important Notes

✅ **Default Value:** "Full Price" is set automatically for new properties  
✅ **Mandatory:** price_type is required - cannot be empty  
✅ **Flexible:** Can be extended to add more price types later  
✅ **Backward Compatible:** Existing properties get the default value  
