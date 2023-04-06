<?php

namespace Irishdistillers\ShopifyStorefrontCheckout\Laravel\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Irishdistillers\ShopifyStorefrontCheckout\CartService;
use Irishdistillers\ShopifyStorefrontCheckout\Laravel\Console\Commands\Traits\ChoiceWithAssociativeOptionsTrait;
use Irishdistillers\ShopifyStorefrontCheckout\Laravel\Helpers\ShopifyCartHelper;

/**
 * @codeCoverageIgnore
 */
class ShopifyCartCommand extends Command
{
    use ChoiceWithAssociativeOptionsTrait;

    protected CartService $cart;

    protected bool $includeSellingPlanAllocation;

    public function __construct()
    {
        $this->includeSellingPlanAllocation = false;
        parent::__construct();
    }

    protected function actions(): array
    {
        return [
            'create' => 'Create new cart',
            'get' => 'Get cart',
            'get_json' => 'Get cart (full json)',
            'add' => 'Add line item to the cart',
            'update' => 'Update line item in the cart',
            'remove' => 'Remove line items from the cart',
            'empty' => 'Empty cart',
            'update_note' => 'Update note in the cart',
            'update_attributes' => 'Update attributes in the cart',
            'update_discount_codes' => 'Update discount codes in the cart',
            'exit' => 'Exit',
        ];
    }

    protected function printLastError()
    {
        $lastError = $this->cart->getLastError();
        if ($lastError) {
            $this->warn('There was an error');
            $this->warn(json_encode($lastError, JSON_PRETTY_PRINT));
            $this->newLine();
        }
    }

    protected function pluck(array $array, bool $beautifyKey = true): array
    {
        $ret = [];
        foreach ($array as $key => $value) {
            $ret[] = [
                $beautifyKey ? ucfirst(str_replace('_', ' ', $key)) : $key,
                $value,
            ];
        }

        return $ret;
    }

    protected function printTable(array $array, array $headers, array $keys, array $defaultValues = [])
    {
        $rows = [];
        foreach ($array as $item) {
            $row = [];
            foreach ($keys as $key) {
                $value = $item[$key] ?? '';
                if (is_bool($value)) {
                    $value = json_encode($value);
                } elseif (empty($value)) {
                    $value = $defaultValues[$key] ?? '';
                }
                $row[$key] = $value;
            }
            $rows[] = $row;
        }
        $this->table($headers, $rows);
    }

    protected function printCart(?string $cartId, ?string $countryCode, bool $pretty)
    {
        // Get cart
        $cart = $this->cart->getCart($cartId, $countryCode, true, $this->includeSellingPlanAllocation);

        if ($cart) {
            $this->alert('Cart '.$cartId);

            $beautifier = $this->cart->beautifier($cart);

            $properties = [];
            $properties[] = ['Cart ID', $beautifier->getCartId()];
            $properties[] = ['Country code', $beautifier->getCountryCode()];
            $properties[] = ['Checkout URL', $beautifier->getCheckoutUrl()];
            $properties[] = ['Note', $beautifier->getNote()];
            if ($pretty) {
                $properties[] = ['Created at', $beautifier->getCreatedAt()];
                $properties[] = ['Updated at', $beautifier->getUpdatedAt()];
            }

            $this->info('=== Properties');
            $this->table(['Property', 'Value'], $properties);
            $this->newLine();

            if ($pretty) {
                // Line items
                $this->info('=== Line items');
                $lineItems = $beautifier->getLineItems(true);
                if (count($lineItems)) {
                    foreach ($lineItems as $index => $lineItem) {
                        $this->info('Line item #'.$index);
                        $this->table(['Property', 'Value'], $this->pluck($lineItem));
                        $this->newLine();
                    }
                } else {
                    $this->line('No items in the cart');
                    $this->newLine();
                }

                // Attributes
                $this->info('=== Attributes');
                $attributes = $beautifier->getAttributes();
                if (count($attributes)) {
                    $this->printTable(
                        $attributes,
                        ['Key', 'Value'],
                        ['key', 'value'],
                        ['value' => '(empty)']
                    );
                } else {
                    $this->line('No attributes in the cart');
                }
                $this->newLine();

                // Discount codes
                $this->info('=== Discount codes');
                $discountCodes = $beautifier->getDiscountCodes();
                if (count($discountCodes)) {
                    $this->printTable(
                        $discountCodes,
                        ['Code', 'Applicable'],
                        ['code', 'applicable'],
                        ['code' => '(empty)']
                    );
                } else {
                    $this->line('No discount codes in the cart');
                }
                $this->newLine();

                // Amounts
                $this->info('=== Estimated costs');
                $this->table(['Type', 'Price'], $this->pluck($beautifier->getEstimatedCosts()));
                $this->newLine();
            }
        }

        $this->printLastError();

        if (! $pretty) {
            $this->line(json_encode($cart, JSON_PRETTY_PRINT));
        }
    }

    protected function askCartId(): string
    {
        $cartId = $this->ask('Cart ID (e.g. 123456 or gid://shopify/Cart/123456)');
        if (substr($cartId, 0, 4) !== 'gid:') {
            $cartId = 'gid://shopify/Cart/'.$cartId;
        }

        return $cartId;
    }

    protected function askMarketCountryCode(): string
    {
        return $this->ask('Market country code (e.g. IE)', 'IE');
    }

    protected function askProductVariantId(): string
    {
        $variantId = $this->ask('Product variant ID (e.g. 123456 or gid://shopify/ProductVariant/123456)');
        if (substr($variantId, 0, 4) !== 'gid:') {
            $variantId = 'gid://shopify/ProductVariant/'.$variantId;
        }

        return $variantId;
    }

    protected function askLineItemId(?array $cart): string
    {
        $lineItems = [];

        foreach ($this->cart->beautifier($cart)->getLineItems() as $lineItem) {
            $lineItems[$lineItem['id']] = $lineItem['title'].' - Quantity: '.$lineItem['quantity'].' - '.$lineItem['price'];
        }
        $lineItems['skip'] = 'Cancel (no item selected)';

        return $this->askChoiceWithAssociatedOptions('Line item ID', $lineItems);
    }

    protected function handle_create()
    {
        $this->alert('Create new cart');

        // Country code
        $countryCode = $this->askMarketCountryCode();

        // Create cart
        $cartId = $this->cart->getNewCart($countryCode);

        $this->printLastError();

        $this->line('Cart ID: '.$cartId);
    }

    protected function handle_get()
    {
        $this->alert('Get cart');

        // Cart ID
        $cartId = $this->askCartId();

        // Country code
        $countryCode = $this->askMarketCountryCode();

        $this->printCart($cartId, $countryCode, true);
    }

    protected function handle_get_json()
    {
        $this->alert('Get cart (full JSON)');

        // Cart ID
        $cartId = $this->askCartId();

        // Country code
        $countryCode = $this->askMarketCountryCode();

        $this->printCart($cartId, $countryCode, false);
    }

    protected function handle_add()
    {
        $this->alert('Add line items to cart');

        // Cart ID
        $cartId = $this->askCartId();

        // Country code
        $countryCode = $this->askMarketCountryCode();

        // Line item
        $variantId = $this->askProductVariantId();
        $quantity = $this->ask('Quantity (1 or more)');
        $this->cart->addLine($cartId, $variantId, $quantity);

        $this->printLastError();

        // Get refreshed cart
        $this->printCart($cartId, $countryCode, true);
    }

    protected function handle_update()
    {
        $this->alert('Update line item on the cart');

        // Cart ID
        $cartId = $this->askCartId();

        // Country code
        $countryCode = $this->askMarketCountryCode();

        // Get cart
        $cart = $this->cart->getCart($cartId, $countryCode);

        // Get cart line item
        $lineItemId = $this->askLineItemId($cart);

        // Check if cancelled
        if ($lineItemId === 'skip') {
            $this->warn('Update line item was cancelled');

            return;
        }

        // Get current quantity
        $lineItemData = $this->cart->beautifier($cart)->getLineItem($lineItemId);
        $currentQuantity = $lineItemData['quantity'] ?? 0;

        // Get new quantity
        $quantity = $this->ask('New quantity', $currentQuantity);

        // Update cart
        $this->cart->updateLine($cartId, $lineItemId, $quantity);

        $this->printLastError();

        // Get refreshed cart
        $this->printCart($cartId, $countryCode, true);
    }

    protected function handle_remove()
    {
        $this->alert('Remove line items from cart');

        // Cart ID
        $cartId = $this->askCartId();

        // Country code
        $countryCode = $this->askMarketCountryCode();

        // Get cart
        $cart = $this->cart->getCart($cartId, $countryCode);

        // Get cart line item
        $lineItemId = $this->askLineItemId($cart);

        // Check if cancelled
        if ($lineItemId === 'skip') {
            $this->warn('Remove line item was cancelled');

            return;
        }

        // Remove line items
        $this->cart->removeLines($cartId, [$lineItemId]);

        $this->printLastError();

        // Get refreshed cart
        $this->printCart($cartId, $countryCode, true);
    }

    protected function handle_empty()
    {
        $this->alert('Empty cart');

        // Cart ID
        $cartId = $this->askCartId();

        // Country code
        $countryCode = $this->askMarketCountryCode();

        // Get cart
        $result = $this->cart->emptyCart($cartId, $countryCode);
        if ($result) {
            // Get refreshed cart
            $this->printCart($cartId, $countryCode, true);
        } else {
            $this->error('Emptying cart failed');
        }
    }

    public function handle_update_note()
    {
        $this->alert('Update note in the cart');

        // Cart ID
        $cartId = $this->askCartId();

        // Country code
        $countryCode = $this->askMarketCountryCode();

        // Note
        $note = $this->ask('Note', '');

        // Update note
        $this->cart->updateNote($cartId, $note);

        $this->printLastError();

        // Get refreshed cart
        $this->printCart($cartId, $countryCode, true);
    }

    public function handle_update_attributes()
    {
        $this->alert('Update attributes in the cart');

        // Cart ID
        $cartId = $this->askCartId();

        // Country code
        $countryCode = $this->askMarketCountryCode();

        // Key and value
        $key = $this->ask('Key', '');
        $value = $this->ask('Value', '');

        // Update note
        $this->cart->updateAttributes($cartId, $key, $value);

        $this->printLastError();

        // Get refreshed cart
        $this->printCart($cartId, $countryCode, true);
    }

    public function handle_update_discount_codes()
    {
        $this->alert('Update discount codes in the cart');

        // Cart ID
        $cartId = $this->askCartId();

        // Country code
        $countryCode = $this->askMarketCountryCode();

        // Discount code
        $discountCode = $this->ask('Discount code, e.g. TENPERCENT.', '');

        // Update discount code
        $this->cart->updateDiscountCodes($cartId, [$discountCode]);

        $this->printLastError();

        // Get refreshed cart
        $this->printCart($cartId, $countryCode, true);
    }

    /**
     * Initialise cart.
     * Override in subclasses to change the initialisation.
     *
     * @return void
     * @throws Exception
     */
    protected function initCart(): void
    {
        $this->cart = ShopifyCartHelper::getNewCartService();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $this->initCart();

            $this->includeSellingPlanAllocation = $this->confirm('Include selling plan allocation in cart object? It requires unauthenticated_read_selling_plans access scope.');

            while (true) {
                $this->alert('Shopify Cart - Please make a choice');

                $action = $this->askChoiceWithAssociatedOptions('Choose an action', $this->actions());

                // Exit loop
                if ($action === 'exit') {
                    $this->line('Bye!');
                    $this->newLine();
                    break;
                }

                $method = 'handle_'.$action;
                if (method_exists($this, $method)) {
                    try {
                        $this->$method();
                    } catch (Exception $e) {
                        Log::error('ShopifyCart, action "'.$action.'" failed', ['e' => $e->getMessage()]);
                        $this->error('Failure: '.$e->getMessage());
                    }
                } else {
                    $this->error('Required action does not yet exist (to be implemented)');
                    break;
                }

                $this->newLine();
            }

            return 0;
        } catch(Exception $e) {
            $this->error('Unable to initialise cart service: '.$e->getMessage());

            return 1;
        }
    }
}
