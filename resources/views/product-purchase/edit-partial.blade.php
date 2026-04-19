@include('product-purchase._form', [
    'action' => route('purchase.update', $purchase->id), 
    'method' => 'POST', 
    'purchase' => $purchase, 
    'products' => $products, 
    'isEdit' => true
])
