# Step 09 — Bangladesh (BD) Localization

## Goal
Add BD-specific formatting options that make this tool feel native for Bangladeshi business users: lakh/crore number grouping, Taka currency, DD/MM/YYYY dates, and Bengali CSV encoding.

## Number Formatting: Lakh / Crore

Bangladesh uses the South Asian numbering system:
- 1,00,000 = 1 lakh (100,000)
- 1,00,00,000 = 1 crore (10,000,000)

### JS Formatter

```js
/**
 * Format a number using BD lakh/crore grouping.
 * @param {number} n
 * @returns {string}
 */
function formatBDNumber(n) {
    const str = Math.abs(n).toString();
    const [intPart, decPart] = str.split('.');

    let result = '';
    if (intPart.length > 3) {
        result = intPart.slice(-3); // last 3 digits
        let remaining = intPart.slice(0, -3);
        while (remaining.length > 2) {
            result = remaining.slice(-2) + ',' + result;
            remaining = remaining.slice(0, -2);
        }
        result = remaining + ',' + result;
    } else {
        result = intPart;
    }

    if (decPart) result += '.' + decPart;
    return (n < 0 ? '-' : '') + result;
}

// Examples:
// formatBDNumber(100000)     → "1,00,000"
// formatBDNumber(10000000)   → "1,00,00,000"
// formatBDNumber(1234567.89) → "12,34,567.89"
```

### Toggle in Settings

```js
Alpine.store('settings', {
    numberFormat: localStorage.getItem('pt-number-format') || 'western', // 'western' | 'bd'
    dateFormat: localStorage.getItem('pt-date-format') || 'dd/mm/yyyy',
    currencySymbol: localStorage.getItem('pt-currency') || '৳',

    setNumberFormat(format) {
        this.numberFormat = format;
        localStorage.setItem('pt-number-format', format);
    },
    setDateFormat(format) {
        this.dateFormat = format;
        localStorage.setItem('pt-date-format', format);
    },
    setCurrencySymbol(sym) {
        this.currencySymbol = sym;
        localStorage.setItem('pt-currency', sym);
    }
});
```

### Updated `formatCell()` to Respect Settings

```js
function formatCell(value, type) {
    const settings = Alpine.store('settings');
    const useBD = settings.numberFormat === 'bd';

    switch (type) {
        case 'integer': {
            const n = parseInt(String(value).replace(/[^\d-]/g, ''));
            return useBD ? formatBDNumber(n) : n.toLocaleString();
        }
        case 'decimal': {
            const n = parseFloat(String(value).replace(/[^\d.-]/g, ''));
            if (useBD) return formatBDNumber(n);
            return n.toLocaleString(undefined, { minimumFractionDigits: 2 });
        }
        case 'currency': {
            const sym = settings.currencySymbol || '৳';
            const n = parseFloat(String(value).replace(/[^\d.-]/g, ''));
            const formatted = useBD
                ? formatBDNumber(parseFloat(n.toFixed(2)))
                : n.toLocaleString(undefined, { minimumFractionDigits: 2 });
            return `${sym}${formatted}`;
        }
        // ... other types unchanged
    }
}
```

## Date Format

Default for BD: `DD/MM/YYYY`. The detection engine already prefers this pattern.

```js
function formatDate(raw, format) {
    // Try to parse raw date value
    const d = new Date(raw);
    if (isNaN(d.getTime())) return raw; // unparseable → return as-is

    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();

    switch (format) {
        case 'dd/mm/yyyy': return `${dd}/${mm}/${yyyy}`;
        case 'mm/dd/yyyy': return `${mm}/${dd}/${yyyy}`;
        case 'yyyy-mm-dd': return `${yyyy}-${mm}-${dd}`;
        default:           return `${dd}/${mm}/${yyyy}`;
    }
}
```

## Bengali CSV Encoding

SheetJS reads XLSX correctly for any language. For raw CSV files:

```js
// In the file reading logic, detect and handle encoding
function readCSVWithEncoding(file) {
    return new Promise((resolve) => {
        const reader = new FileReader();

        // First try UTF-8
        reader.onload = (e) => {
            const text = e.target.result;

            // Check for obvious mojibake (replacement char)
            if (text.includes('\uFFFD')) {
                // Re-read as windows-1252 (common legacy encoding for BD software)
                const reader2 = new FileReader();
                reader2.onload = (e2) => resolve(e2.target.result);
                reader2.readAsText(file, 'windows-1252');
            } else {
                resolve(text);
            }
        };

        reader.readAsText(file, 'UTF-8');
    });
}
```

For SheetJS CSV reading:
```js
// Pass codepage option for legacy files
const wb = XLSX.read(text, { type: 'string', codepage: 65001 }); // UTF-8
```

## Currency Symbol Options

The settings panel should offer:
- ৳ (Taka — default)
- $ (USD)
- £ (GBP)
- € (EUR)
- Custom (free text input)

## Settings Panel UI

A dedicated "Localization" section in the settings sidebar:

```html
<div class="settings-section border-t border-[--border] pt-4 mt-4">
    <h4 class="font-semibold text-sm mb-3">Localization</h4>

    <!-- Number grouping -->
    <label class="block text-sm mb-1">Number format</label>
    <select @change="$store.settings.setNumberFormat($event.target.value)" class="w-full text-sm ...">
        <option value="western">Western (1,234,567)</option>
        <option value="bd">BD (12,34,567)</option>
    </select>

    <!-- Currency symbol -->
    <label class="block text-sm mt-3 mb-1">Currency symbol</label>
    <select @change="$store.settings.setCurrencySymbol($event.target.value)" class="w-full text-sm ...">
        <option value="৳">৳ Taka</option>
        <option value="$">$ USD</option>
        <option value="£">£ GBP</option>
        <option value="€">€ EUR</option>
    </select>

    <!-- Date format -->
    <label class="block text-sm mt-3 mb-1">Date format</label>
    <select @change="$store.settings.setDateFormat($event.target.value)" class="w-full text-sm ...">
        <option value="dd/mm/yyyy">DD/MM/YYYY (BD default)</option>
        <option value="mm/dd/yyyy">MM/DD/YYYY (US)</option>
        <option value="yyyy-mm-dd">YYYY-MM-DD (ISO)</option>
    </select>
</div>
```

## Checklist
- [ ] `formatBDNumber()` function implemented and tested
- [ ] `settings` Alpine store with `numberFormat`, `dateFormat`, `currencySymbol`
- [ ] Settings persisted to localStorage
- [ ] `formatCell()` updated to check `settings.numberFormat` for integer/decimal/currency
- [ ] `formatDate()` helper respects `settings.dateFormat`
- [ ] Currency symbol reads from `settings.currencySymbol`
- [ ] Localization section added to settings panel UI
- [ ] Bengali CSV encoding detection (UTF-8 → windows-1252 fallback)
- [ ] Date detection includes `DD/MM/YYYY` as highest-priority pattern
- [ ] Letterhead date field defaults to BD format `DD/MM/YYYY`
