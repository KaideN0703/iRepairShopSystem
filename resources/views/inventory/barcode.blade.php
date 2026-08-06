<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barcode Sticker - {{ $part->sku }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; padding: 20px; text-align: center; }
        .sticker { width: 300px; margin: 0 auto; background: white; border: 2px solid #0f172a; padding: 15px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .part-title { font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #0f172a; }
        .sku { font-size: 11px; color: #64748b; margin-bottom: 10px; }
        .barcode-lines { height: 50px; background: repeating-linear-gradient(90deg, #000, #000 2px, #fff 2px, #fff 4px, #000 4px, #000 7px, #fff 7px, #fff 9px); margin: 10px 0; }
        .price { font-size: 16px; font-weight: bold; color: #4338ca; }
        .rack { font-size: 10px; color: #64748b; }
        @media print { button { display: none; } body { background: white; padding: 0; } }
    </style>
</head>
<body>
    <button onclick="window.print()" style="padding: 8px 16px; margin-bottom: 20px; font-weight: bold; cursor: pointer;">🖨️ Print Barcode Label</button>

    <div class="sticker">
        <div class="part-title">{{ $part->name }}</div>
        <div class="sku">SKU: {{ $part->sku }}</div>
        <div class="barcode-lines"></div>
        <div style="font-family: monospace; font-size: 12px; margin-bottom: 5px;">*{{ $part->barcode }}*</div>
        <div class="price">₱{{ number_format($part->selling_price, 2) }}</div>
        <div class="rack">Rack: {{ $part->location_rack ?? 'General Stock' }}</div>
    </div>
</body>
</html>
