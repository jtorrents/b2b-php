# Developer Setup Guide - B2BRouter PHP SDK

This guide will walk you through setting up a complete PHP development environment on Debian 13, configuring Neovim for PHP development, and working with the B2BRouter PHP SDK.

## Table of Contents

- [PHP Development Environment Setup (Debian 13)](#php-development-environment-setup-debian-13)
- [Neovim Setup for PHP Development](#neovim-setup-for-php-development)
- [Working with the SDK](#working-with-the-sdk)
- [Running Tests](#running-tests)
- [Creating Invoices - Examples](#creating-invoices---examples)
- [Debugging](#debugging)
- [Best Practices](#best-practices)

---

## PHP Development Environment Setup (Debian 13)

### 1. Update System Packages

```bash
sudo apt update
sudo apt upgrade -y
```

### 2. Install PHP 8.4 and Required Extensions

```bash
# Add Sury PHP repository for latest PHP versions
sudo apt install -y lsb-release ca-certificates curl
sudo curl -sSL https://packages.sury.org/php/README.txt | sudo bash -x

# Update package list
sudo apt update

# Install PHP 8.4 with common extensions
sudo apt install -y \
    php8.4-cli \
    php8.4-curl \
    php8.4-mbstring \
    php8.4-xml \
    php8.4-zip \
    php8.4-intl \
    php8.4-bcmath \
    php8.4-gd \
    php8.4-sqlite3 \
    php8.4-mysql \
    php8.4-pgsql \
    php8.4-xdebug

# Verify installation
php --version
```

Expected output:
```
PHP 8.4.x (cli) (built: ...)
```

### 3. Install Composer

```bash
# Download and install Composer
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

# Verify installation
composer --version
```

### 4. Install Development Tools

```bash
# Install Git (if not already installed)
sudo apt install -y git

# Install build tools
sudo apt install -y build-essential

# Install additional useful tools
sudo apt install -y \
    wget \
    unzip \
    jq \
    httpie
```

### 5. Configure PHP for Development

Edit PHP configuration for CLI:

```bash
sudo nano /etc/php/8.4/cli/php.ini
```

Recommended settings for development:

```ini
; Display errors
display_errors = On
display_startup_errors = On
error_reporting = E_ALL

; Memory limit
memory_limit = 512M

; Time limits
max_execution_time = 300
max_input_time = 300

; OPcache (for better performance)
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=0
opcache.validate_timestamps=1

; Xdebug (for debugging)
xdebug.mode=debug,coverage
xdebug.start_with_request=trigger
xdebug.client_host=localhost
xdebug.client_port=9003
```

### 6. Verify PHP Extensions

```bash
php -m | grep -E 'curl|json|mbstring|xml|xdebug'
```

You should see all required extensions listed.

---

## Neovim Setup for PHP Development

### 1. Install Neovim

```bash
# Install latest Neovim from unstable repo (for best LSP support)
sudo apt install -y neovim

# Or install from official AppImage for latest version
curl -LO https://github.com/neovim/neovim/releases/latest/download/nvim.appimage
chmod u+x nvim.appimage
sudo mv nvim.appimage /usr/local/bin/nvim

# Verify installation
nvim --version
```

### 2. Install Node.js (required for many Neovim plugins)

```bash
# Install Node.js 20.x
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verify installation
node --version
npm --version
```

### 3. Install Neovim Plugin Manager (lazy.nvim)

Create Neovim configuration directory:

```bash
mkdir -p ~/.config/nvim
```

Create `~/.config/nvim/init.lua`:

```lua
-- Bootstrap lazy.nvim
local lazypath = vim.fn.stdpath("data") .. "/lazy/lazy.nvim"
if not vim.loop.fs_stat(lazypath) then
  vim.fn.system({
    "git",
    "clone",
    "--filter=blob:none",
    "https://github.com/folke/lazy.nvim.git",
    "--branch=stable",
    lazypath,
  })
end
vim.opt.rtp:prepend(lazypath)

-- Basic settings
vim.g.mapleader = " "
vim.g.maplocalleader = " "
vim.opt.number = true
vim.opt.relativenumber = true
vim.opt.tabstop = 4
vim.opt.shiftwidth = 4
vim.opt.expandtab = true
vim.opt.smartindent = true
vim.opt.wrap = false
vim.opt.swapfile = false
vim.opt.backup = false
vim.opt.hlsearch = false
vim.opt.incsearch = true
vim.opt.termguicolors = true
vim.opt.scrolloff = 8
vim.opt.signcolumn = "yes"
vim.opt.updatetime = 50

-- Setup plugins
require("lazy").setup("plugins")
```

### 4. Create Plugin Configuration

Create `~/.config/nvim/lua/plugins/init.lua`:

```lua
return {
  -- Color scheme
  {
    "folke/tokyonight.nvim",
    lazy = false,
    priority = 1000,
    config = function()
      vim.cmd([[colorscheme tokyonight-night]])
    end,
  },

  -- Treesitter for syntax highlighting
  {
    "nvim-treesitter/nvim-treesitter",
    build = ":TSUpdate",
    config = function()
      require("nvim-treesitter.configs").setup({
        ensure_installed = { "php", "lua", "vim", "json", "yaml", "markdown" },
        highlight = { enable = true },
        indent = { enable = true },
      })
    end,
  },

  -- LSP Configuration
  {
    "neovim/nvim-lspconfig",
    dependencies = {
      "williamboman/mason.nvim",
      "williamboman/mason-lspconfig.nvim",
      "hrsh7th/nvim-cmp",
      "hrsh7th/cmp-nvim-lsp",
      "hrsh7th/cmp-buffer",
      "hrsh7th/cmp-path",
      "L3MON4D3/LuaSnip",
      "saadparwaiz1/cmp_luasnip",
    },
    config = function()
      -- Mason setup
      require("mason").setup()
      require("mason-lspconfig").setup({
        ensure_installed = { "intelephense" },
        automatic_installation = true,
      })

      -- Completion setup
      local cmp = require("cmp")
      local luasnip = require("luasnip")

      cmp.setup({
        snippet = {
          expand = function(args)
            luasnip.lsp_expand(args.body)
          end,
        },
        mapping = cmp.mapping.preset.insert({
          ["<C-b>"] = cmp.mapping.scroll_docs(-4),
          ["<C-f>"] = cmp.mapping.scroll_docs(4),
          ["<C-Space>"] = cmp.mapping.complete(),
          ["<C-e>"] = cmp.mapping.abort(),
          ["<CR>"] = cmp.mapping.confirm({ select = true }),
          ["<Tab>"] = cmp.mapping(function(fallback)
            if cmp.visible() then
              cmp.select_next_item()
            elseif luasnip.expand_or_jumpable() then
              luasnip.expand_or_jump()
            else
              fallback()
            end
          end, { "i", "s" }),
        }),
        sources = cmp.config.sources({
          { name = "nvim_lsp" },
          { name = "luasnip" },
          { name = "buffer" },
          { name = "path" },
        }),
      })

      -- LSP keybindings
      local on_attach = function(client, bufnr)
        local opts = { buffer = bufnr, noremap = true, silent = true }
        vim.keymap.set("n", "gd", vim.lsp.buf.definition, opts)
        vim.keymap.set("n", "K", vim.lsp.buf.hover, opts)
        vim.keymap.set("n", "gi", vim.lsp.buf.implementation, opts)
        vim.keymap.set("n", "<leader>rn", vim.lsp.buf.rename, opts)
        vim.keymap.set("n", "<leader>ca", vim.lsp.buf.code_action, opts)
        vim.keymap.set("n", "gr", vim.lsp.buf.references, opts)
        vim.keymap.set("n", "<leader>f", function()
          vim.lsp.buf.format({ async = true })
        end, opts)
      end

      -- Setup Intelephense
      require("lspconfig").intelephense.setup({
        on_attach = on_attach,
        capabilities = require("cmp_nvim_lsp").default_capabilities(),
        settings = {
          intelephense = {
            files = {
              maxSize = 5000000,
            },
            environment = {
              phpVersion = "8.4.0",
            },
          },
        },
      })
    end,
  },

  -- File explorer
  {
    "nvim-tree/nvim-tree.lua",
    dependencies = { "nvim-tree/nvim-web-devicons" },
    config = function()
      require("nvim-tree").setup()
      vim.keymap.set("n", "<leader>e", ":NvimTreeToggle<CR>")
    end,
  },

  -- Fuzzy finder
  {
    "nvim-telescope/telescope.nvim",
    dependencies = { "nvim-lua/plenary.nvim" },
    config = function()
      local builtin = require("telescope.builtin")
      vim.keymap.set("n", "<leader>ff", builtin.find_files, {})
      vim.keymap.set("n", "<leader>fg", builtin.live_grep, {})
      vim.keymap.set("n", "<leader>fb", builtin.buffers, {})
    end,
  },

  -- Git integration
  {
    "lewis6991/gitsigns.nvim",
    config = function()
      require("gitsigns").setup()
    end,
  },

  -- Status line
  {
    "nvim-lualine/lualine.nvim",
    dependencies = { "nvim-tree/nvim-web-devicons" },
    config = function()
      require("lualine").setup({
        options = { theme = "tokyonight" },
      })
    end,
  },

  -- PHP specific tools
  {
    "phpactor/phpactor",
    build = "composer install --no-dev -o",
    ft = "php",
  },

  -- Debugging with DAP
  {
    "mfussenegger/nvim-dap",
    dependencies = {
      "rcarriga/nvim-dap-ui",
      "theHamsta/nvim-dap-virtual-text",
    },
    config = function()
      local dap = require("dap")
      local dapui = require("dapui")

      dapui.setup()
      require("nvim-dap-virtual-text").setup()

      -- PHP debugging with Xdebug
      dap.adapters.php = {
        type = "executable",
        command = "node",
        args = { vim.fn.stdpath("data") .. "/mason/packages/php-debug-adapter/extension/out/phpDebug.js" },
      }

      dap.configurations.php = {
        {
          type = "php",
          request = "launch",
          name = "Listen for Xdebug",
          port = 9003,
        },
      }

      -- Keybindings
      vim.keymap.set("n", "<F5>", dap.continue)
      vim.keymap.set("n", "<F10>", dap.step_over)
      vim.keymap.set("n", "<F11>", dap.step_into)
      vim.keymap.set("n", "<F12>", dap.step_out)
      vim.keymap.set("n", "<leader>b", dap.toggle_breakpoint)
      vim.keymap.set("n", "<leader>dr", dap.repl.open)
      vim.keymap.set("n", "<leader>du", dapui.toggle)
    end,
  },

  -- Comment plugin
  {
    "numToStr/Comment.nvim",
    config = function()
      require("Comment").setup()
    end,
  },

  -- Auto pairs
  {
    "windwp/nvim-autopairs",
    config = function()
      require("nvim-autopairs").setup()
    end,
  },
}
```

### 5. Install PHP Language Server

Open Neovim and run:

```vim
:Lazy sync
:Mason
```

In Mason, press `i` to install:
- `intelephense` (PHP language server)
- `php-debug-adapter` (for debugging)
- `phpstan` (optional, for static analysis)
- `php-cs-fixer` (optional, for code formatting)

### 6. Create Neovim PHP Keybindings

Add to `~/.config/nvim/lua/plugins/init.lua` or create a separate file:

```lua
-- PHP specific keybindings
vim.api.nvim_create_autocmd("FileType", {
  pattern = "php",
  callback = function()
    local opts = { buffer = true, noremap = true, silent = true }

    -- Run PHPUnit tests
    vim.keymap.set("n", "<leader>t", ":!vendor/bin/phpunit<CR>", opts)
    vim.keymap.set("n", "<leader>tf", ":!vendor/bin/phpunit %<CR>", opts)

    -- Run current test method
    vim.keymap.set("n", "<leader>tm", function()
      local line = vim.fn.search("function test", "bnW")
      local method = vim.fn.getline(line):match("function (test%w+)")
      if method then
        vim.cmd("!vendor/bin/phpunit --filter " .. method)
      end
    end, opts)

    -- Run Composer commands
    vim.keymap.set("n", "<leader>ci", ":!composer install<CR>", opts)
    vim.keymap.set("n", "<leader>cu", ":!composer update<CR>", opts)

    -- Run PHP syntax check
    vim.keymap.set("n", "<leader>ps", ":!php -l %<CR>", opts)
  end,
})
```

### 7. Neovim Quick Reference

**Essential Keybindings (with Space as leader):**

| Key | Action |
|-----|--------|
| `<Space>e` | Toggle file explorer |
| `<Space>ff` | Find files |
| `<Space>fg` | Search in files (grep) |
| `<Space>fb` | List buffers |
| `gd` | Go to definition |
| `K` | Show hover documentation |
| `<Space>rn` | Rename symbol |
| `<Space>ca` | Code action |
| `<Space>f` | Format code |
| `<Space>t` | Run all tests |
| `<Space>tf` | Run tests in current file |
| `<F5>` | Start/continue debugging |
| `<Space>b` | Toggle breakpoint |

---

## Working with the SDK

### 1. Clone and Setup the SDK

```bash
# Clone the repository
git clone https://github.com/yourusername/b2b-php.git
cd b2b-php

# Install dependencies
composer install

# Verify installation
vendor/bin/phpunit --version
```

### 2. Project Structure

```
b2b-php/
├── lib/B2BRouter/          # SDK source code
│   ├── B2BRouterClient.php # Main client
│   ├── ApiResource.php     # Base resource class
│   ├── Collection.php      # Pagination support
│   ├── Exception/          # Exception classes
│   ├── HttpClient/         # HTTP layer
│   └── Service/
│       └── InvoiceService.php
├── tests/                  # Test suite
│   ├── Unit/              # Unit tests
│   └── Mock/              # Mock objects
├── examples/              # Usage examples
├── composer.json          # Dependencies
└── phpunit.xml.dist      # Test configuration
```

### 3. Configuration

Create a `.env` file (never commit this!):

```bash
# .env
B2B_API_KEY=your_api_key_here
B2B_ACCOUNT_ID=your_account_id
B2B_API_BASE=https://api-staging.b2brouter.net
B2B_API_VERSION=2025-10-13
```

Load environment variables in your PHP code:

```php
<?php
// Load environment variables (using vlucas/phpdotenv)
// composer require vlucas/phpdotenv

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['B2B_API_KEY'];
$accountId = $_ENV['B2B_ACCOUNT_ID'];
```

---

## Running Tests

### 1. Run All Tests

```bash
# Standard output
vendor/bin/phpunit

# With detailed output
vendor/bin/phpunit --testdox

# With colors
vendor/bin/phpunit --testdox --colors=always
```

### 2. Run Specific Test Suites

```bash
# Run only unit tests
vendor/bin/phpunit tests/Unit

# Run specific test file
vendor/bin/phpunit tests/Unit/InvoiceServiceTest.php

# Run specific test method
vendor/bin/phpunit --filter testCreateInvoice

# Run tests matching a pattern
vendor/bin/phpunit --filter Invoice
```

### 3. Run Tests with Coverage

```bash
# Generate HTML coverage report
vendor/bin/phpunit --coverage-html coverage

# Open coverage report
firefox coverage/index.html

# Generate coverage text report
vendor/bin/phpunit --coverage-text

# Generate Clover XML (for CI)
vendor/bin/phpunit --coverage-clover coverage.xml
```

### 4. Run Tests in Watch Mode

Install `phpunit-watcher`:

```bash
composer require --dev spatie/phpunit-watcher
```

Run tests automatically on file changes:

```bash
vendor/bin/phpunit-watcher watch
```

### 5. Run Tests with Xdebug

```bash
# Enable Xdebug
export XDEBUG_MODE=coverage,debug

# Run tests with debugger
php -dxdebug.mode=debug -dxdebug.start_with_request=yes \
    vendor/bin/phpunit --filter testCreateInvoice
```

### 6. Continuous Integration Example

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    strategy:
      matrix:
        php-version: ['8.1', '8.2', '8.3', '8.4']

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          extensions: curl, json, mbstring
          coverage: xdebug

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run tests
        run: vendor/bin/phpunit --coverage-text
```

---

## Creating Invoices - Examples

### Example 1: Simple Invoice Creation

Create `examples/create_simple_invoice.php`:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use B2BRouter\B2BRouterClient;
use B2BRouter\Exception\ApiErrorException;

// Initialize client
$client = new B2BRouterClient($_ENV['B2B_API_KEY'] ?? 'your-api-key');
$accountId = $_ENV['B2B_ACCOUNT_ID'] ?? 'your-account-id';

try {
    // Create a simple invoice
    $invoice = $client->invoices->create($accountId, [
        'invoice' => [
            'number' => 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'currency' => 'EUR',
            'buyer' => [
                'name' => 'Acme Corporation',
                'tax_id' => 'ESB12345678',
                'country' => 'ES',
                'address' => [
                    'street' => 'Calle Mayor 1',
                    'city' => 'Madrid',
                    'postal_code' => '28001',
                    'country' => 'ES',
                ],
                'email' => 'billing@acme.com',
            ],
            'seller' => [
                'name' => 'Your Company SL',
                'tax_id' => 'ESA87654321',
                'country' => 'ES',
            ],
            'lines' => [
                [
                    'description' => 'Professional Services - January 2025',
                    'quantity' => 1,
                    'unit_price' => 1000.00,
                    'amount' => 1000.00,
                    'tax_rate' => 21.0,
                    'tax_amount' => 210.00,
                ]
            ],
            'total_before_tax' => 1000.00,
            'total_tax' => 210.00,
            'total_amount' => 1210.00,
        ],
        'send_after_import' => false
    ], [
        'idempotency_key' => 'invoice_' . uniqid()
    ]);

    echo "✓ Invoice created successfully!\n";
    echo "  ID: {$invoice['id']}\n";
    echo "  Number: {$invoice['number']}\n";
    echo "  Total: {$invoice['total_amount']} {$invoice['currency']}\n";

} catch (ApiErrorException $e) {
    echo "✗ Error creating invoice:\n";
    echo "  {$e->getMessage()}\n";
    echo "  Status: {$e->getHttpStatus()}\n";

    if ($e->getJsonBody()) {
        echo "  Details: " . json_encode($e->getJsonBody(), JSON_PRETTY_PRINT) . "\n";
    }

    exit(1);
}
```

Run the example:

```bash
php examples/create_simple_invoice.php
```

### Example 2: Invoice with Multiple Line Items

Create `examples/create_detailed_invoice.php`:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use B2BRouter\B2BRouterClient;

$client = new B2BRouterClient($_ENV['B2B_API_KEY']);
$accountId = $_ENV['B2B_ACCOUNT_ID'];

// Define invoice line items
$items = [
    [
        'description' => 'Web Development - 40 hours',
        'quantity' => 40,
        'unit_price' => 75.00,
        'tax_rate' => 21.0,
    ],
    [
        'description' => 'UI/UX Design - 20 hours',
        'quantity' => 20,
        'unit_price' => 85.00,
        'tax_rate' => 21.0,
    ],
    [
        'description' => 'Project Management - 10 hours',
        'quantity' => 10,
        'unit_price' => 95.00,
        'tax_rate' => 21.0,
    ],
];

// Calculate totals
$lines = [];
$subtotal = 0;
$totalTax = 0;

foreach ($items as $item) {
    $amount = $item['quantity'] * $item['unit_price'];
    $taxAmount = $amount * ($item['tax_rate'] / 100);

    $lines[] = [
        'description' => $item['description'],
        'quantity' => $item['quantity'],
        'unit_price' => $item['unit_price'],
        'amount' => $amount,
        'tax_rate' => $item['tax_rate'],
        'tax_amount' => $taxAmount,
    ];

    $subtotal += $amount;
    $totalTax += $taxAmount;
}

$total = $subtotal + $totalTax;

try {
    $invoice = $client->invoices->create($accountId, [
        'invoice' => [
            'number' => 'INV-2025-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'currency' => 'EUR',
            'buyer' => [
                'name' => 'Tech Startup Inc',
                'tax_id' => 'ESB98765432',
                'country' => 'ES',
                'email' => 'accounts@techstartup.com',
            ],
            'lines' => $lines,
            'total_before_tax' => $subtotal,
            'total_tax' => $totalTax,
            'total_amount' => $total,
            'notes' => 'Payment terms: 30 days net. Bank transfer preferred.',
        ]
    ]);

    echo "✓ Detailed invoice created!\n";
    echo "  Invoice: {$invoice['number']}\n";
    echo "  Items: " . count($lines) . "\n";
    echo "  Subtotal: €" . number_format($subtotal, 2) . "\n";
    echo "  Tax (21%): €" . number_format($totalTax, 2) . "\n";
    echo "  Total: €" . number_format($total, 2) . "\n";

} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
    exit(1);
}
```

### Example 3: Retrieve and Update Invoice

Create `examples/update_invoice.php`:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use B2BRouter\B2BRouterClient;

$client = new B2BRouterClient($_ENV['B2B_API_KEY']);
$invoiceId = $argv[1] ?? null;

if (!$invoiceId) {
    echo "Usage: php update_invoice.php <invoice_id>\n";
    exit(1);
}

try {
    // Retrieve existing invoice
    echo "Fetching invoice {$invoiceId}...\n";
    $invoice = $client->invoices->retrieve($invoiceId);

    echo "Current status: {$invoice['status']}\n";
    echo "Current notes: " . ($invoice['notes'] ?? 'None') . "\n\n";

    // Update invoice
    echo "Updating invoice...\n";
    $updated = $client->invoices->update($invoiceId, [
        'invoice' => [
            'notes' => 'Updated on ' . date('Y-m-d H:i:s') . ' - Payment reminder sent',
            'metadata' => [
                'reminder_sent' => true,
                'reminder_date' => date('c'),
            ],
        ]
    ]);

    echo "✓ Invoice updated successfully!\n";
    echo "  New notes: {$updated['notes']}\n";

} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
    exit(1);
}
```

### Example 4: List and Filter Invoices

Create `examples/list_invoices.php`:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use B2BRouter\B2BRouterClient;

$client = new B2BRouterClient($_ENV['B2B_API_KEY']);
$accountId = $_ENV['B2B_ACCOUNT_ID'];

try {
    echo "Fetching invoices from the last 30 days...\n\n";

    $invoices = $client->invoices->all($accountId, [
        'limit' => 25,
        'offset' => 0,
        'date_from' => date('Y-m-d', strtotime('-30 days')),
        'date_to' => date('Y-m-d'),
    ]);

    echo "Found {$invoices->count()} invoices";
    if ($invoices->getTotal()) {
        echo " (Total: {$invoices->getTotal()})";
    }
    echo "\n\n";

    // Display invoices in a table
    printf("%-15s %-20s %-12s %-10s %s\n",
        'ID', 'Number', 'Date', 'Amount', 'Status');
    echo str_repeat('-', 80) . "\n";

    foreach ($invoices as $invoice) {
        printf("%-15s %-20s %-12s %-10s %s\n",
            substr($invoice['id'], 0, 12) . '...',
            $invoice['number'] ?? 'N/A',
            $invoice['issue_date'] ?? 'N/A',
            $invoice['currency'] . ' ' . number_format($invoice['total_amount'] ?? 0, 2),
            $invoice['status'] ?? 'unknown'
        );
    }

    echo "\n";

    // Check for more pages
    if ($invoices->hasMore()) {
        echo "Note: More invoices available. Use offset parameter to fetch next page.\n";
        echo "Example: \$invoices = \$client->invoices->all(\$accountId, ['offset' => 25]);\n";
    }

} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
    exit(1);
}
```

### Example 5: Paginate Through All Invoices

Create `examples/paginate_all_invoices.php`:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use B2BRouter\B2BRouterClient;

$client = new B2BRouterClient($_ENV['B2B_API_KEY']);
$accountId = $_ENV['B2B_ACCOUNT_ID'];

try {
    $offset = 0;
    $limit = 50;
    $allInvoices = [];
    $pageCount = 0;

    echo "Fetching all invoices...\n";

    do {
        $pageCount++;
        echo "  Page {$pageCount}... ";

        $page = $client->invoices->all($accountId, [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $count = $page->count();
        echo "{$count} invoices\n";

        foreach ($page as $invoice) {
            $allInvoices[] = $invoice;
        }

        $offset += $limit;

        // Optional: Add delay to respect rate limits
        usleep(100000); // 100ms

    } while ($page->hasMore());

    echo "\n✓ Fetched all " . count($allInvoices) . " invoices!\n";

    // Analyze invoices
    $totalAmount = 0;
    $currencies = [];

    foreach ($allInvoices as $invoice) {
        $currency = $invoice['currency'] ?? 'EUR';
        $amount = $invoice['total_amount'] ?? 0;

        if (!isset($currencies[$currency])) {
            $currencies[$currency] = 0;
        }
        $currencies[$currency] += $amount;
    }

    echo "\nSummary by currency:\n";
    foreach ($currencies as $currency => $amount) {
        echo "  {$currency}: " . number_format($amount, 2) . "\n";
    }

} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
    exit(1);
}
```

### Example 6: Complete Invoice Workflow

Create `examples/invoice_workflow.php`:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use B2BRouter\B2BRouterClient;
use B2BRouter\Exception\ApiErrorException;

$client = new B2BRouterClient($_ENV['B2B_API_KEY']);
$accountId = $_ENV['B2B_ACCOUNT_ID'];

function step($message) {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "STEP: {$message}\n";
    echo str_repeat('=', 60) . "\n";
}

try {
    step("1. Create Invoice");

    $invoice = $client->invoices->create($accountId, [
        'invoice' => [
            'number' => 'WORKFLOW-' . date('Ymd-His'),
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'currency' => 'EUR',
            'buyer' => [
                'name' => 'Demo Customer',
                'tax_id' => 'ESB11111111',
                'country' => 'ES',
                'email' => 'demo@example.com',
            ],
            'lines' => [
                [
                    'description' => 'Consulting Services',
                    'quantity' => 1,
                    'unit_price' => 500.00,
                    'amount' => 500.00,
                    'tax_rate' => 21.0,
                    'tax_amount' => 105.00,
                ]
            ],
            'total_before_tax' => 500.00,
            'total_tax' => 105.00,
            'total_amount' => 605.00,
        ]
    ]);

    $invoiceId = $invoice['id'];
    echo "✓ Invoice created: {$invoice['number']} (ID: {$invoiceId})\n";

    sleep(1);

    step("2. Retrieve Invoice");

    $retrieved = $client->invoices->retrieve($invoiceId);
    echo "✓ Retrieved invoice: {$retrieved['number']}\n";
    echo "  Status: {$retrieved['status']}\n";
    echo "  Amount: {$retrieved['total_amount']} {$retrieved['currency']}\n";

    sleep(1);

    step("3. Validate Invoice");

    $validation = $client->invoices->validate($invoiceId);
    echo "✓ Validation result: " . json_encode($validation, JSON_PRETTY_PRINT) . "\n";

    sleep(1);

    step("4. Update Invoice");

    $updated = $client->invoices->update($invoiceId, [
        'invoice' => [
            'notes' => 'Validated and ready to send - ' . date('Y-m-d H:i:s'),
        ]
    ]);
    echo "✓ Invoice updated\n";

    sleep(1);

    step("5. Mark Invoice as Sent");

    $marked = $client->invoices->markAs($invoiceId, [
        'status' => 'sent'
    ]);
    echo "✓ Invoice marked as sent\n";

    sleep(1);

    step("6. Send Invoice (Optional)");

    // Uncomment to actually send the invoice
    // $sent = $client->invoices->send($invoiceId);
    // echo "✓ Invoice sent to customer\n";
    echo "⚠ Skipped sending (uncomment to enable)\n";

    step("Workflow Complete!");

    echo "\nInvoice lifecycle completed successfully!\n";
    echo "Invoice ID: {$invoiceId}\n";
    echo "Invoice Number: {$invoice['number']}\n";

} catch (ApiErrorException $e) {
    echo "\n✗ API Error occurred:\n";
    echo "  Message: {$e->getMessage()}\n";
    echo "  Status: {$e->getHttpStatus()}\n";
    echo "  Request ID: {$e->getRequestId()}\n";

    if ($e->getJsonBody()) {
        echo "  Details: " . json_encode($e->getJsonBody(), JSON_PRETTY_PRINT) . "\n";
    }

    exit(1);
} catch (Exception $e) {
    echo "\n✗ Error: {$e->getMessage()}\n";
    exit(1);
}
```

Run the workflow:

```bash
php examples/invoice_workflow.php
```

### Running the Examples

```bash
# Make examples directory executable
cd b2b-php

# Set environment variables
export B2B_API_KEY="your_api_key"
export B2B_ACCOUNT_ID="your_account_id"

# Run examples
php examples/create_simple_invoice.php
php examples/create_detailed_invoice.php
php examples/list_invoices.php
php examples/invoice_workflow.php

# Update specific invoice
php examples/update_invoice.php inv_12345
```

---

## Debugging

### 1. Enable Xdebug in Neovim

Start a PHP script with Xdebug:

```bash
export XDEBUG_MODE=debug
export XDEBUG_START_WITH_REQUEST=yes
php examples/create_simple_invoice.php
```

In Neovim:
1. Set breakpoints with `<Space>b`
2. Start debugger with `<F5>`
3. Step through code with `<F10>` (over), `<F11>` (into)
4. View variables in the DAP UI with `<Space>du`

### 2. Debug Tests

```bash
# Run single test with Xdebug
php -dxdebug.mode=debug -dxdebug.start_with_request=yes \
    vendor/bin/phpunit --filter testCreateInvoice

# In another terminal, connect with your IDE/Neovim debugger
```

### 3. Log API Requests

Add logging to the SDK:

```php
// Create a logging HTTP client wrapper
class LoggingHttpClient implements ClientInterface
{
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function request($method, $url, $headers, $body, $timeout)
    {
        error_log("API Request: {$method} {$url}");
        error_log("Headers: " . json_encode($headers));
        error_log("Body: " . json_encode($body));

        $response = $this->client->request($method, $url, $headers, $body, $timeout);

        error_log("API Response Status: {$response['status']}");
        error_log("Response Body: " . substr($response['body'], 0, 500));

        return $response;
    }
}

// Use it
$httpClient = new LoggingHttpClient(new CurlClient());
$client = new B2BRouterClient('api-key', ['http_client' => $httpClient]);
```

---

## Best Practices

### 1. Error Handling

Always wrap API calls in try-catch blocks:

```php
use B2BRouter\Exception\{
    AuthenticationException,
    PermissionException,
    ResourceNotFoundException,
    InvalidRequestException,
    ApiConnectionException,
    ApiErrorException
};

try {
    $invoice = $client->invoices->create($accountId, $params);
} catch (InvalidRequestException $e) {
    // Handle validation errors
    $errors = $e->getJsonBody()['errors'] ?? [];
    foreach ($errors as $field => $messages) {
        echo "Error in {$field}: " . implode(', ', $messages) . "\n";
    }
} catch (AuthenticationException $e) {
    // Handle auth errors
    echo "Authentication failed. Check your API key.\n";
} catch (ResourceNotFoundException $e) {
    // Handle 404 errors
    echo "Resource not found: {$e->getMessage()}\n";
} catch (ApiConnectionException $e) {
    // Handle network errors
    echo "Network error: {$e->getMessage()}\n";
    // Maybe retry?
} catch (ApiErrorException $e) {
    // Handle all other API errors
    echo "API error: {$e->getMessage()}\n";
    echo "Status: {$e->getHttpStatus()}\n";
}
```

### 2. Use Idempotency Keys

For create operations, always use idempotency keys:

```php
$idempotencyKey = 'invoice_' . $userId . '_' . time() . '_' . uniqid();

$invoice = $client->invoices->create($accountId, $params, [
    'idempotency_key' => $idempotencyKey
]);
```

### 3. Pagination

When fetching large datasets, paginate properly:

```php
$offset = 0;
$limit = 100; // Max 500

do {
    $page = $client->invoices->all($accountId, [
        'limit' => $limit,
        'offset' => $offset
    ]);

    processInvoices($page->all());

    $offset += $limit;

    // Respect rate limits
    usleep(100000); // 100ms delay

} while ($page->hasMore());
```

### 4. Environment-Specific Configuration

```php
// config.php
return [
    'development' => [
        'api_base' => 'https://api-staging.b2brouter.net',
        'api_key' => getenv('B2B_DEV_API_KEY'),
        'timeout' => 120,
        'max_retries' => 3,
    ],
    'production' => [
        'api_base' => 'https://api.b2brouter.net',
        'api_key' => getenv('B2B_PROD_API_KEY'),
        'timeout' => 80,
        'max_retries' => 5,
    ],
];

$env = getenv('APP_ENV') ?: 'development';
$config = require 'config.php';

$client = new B2BRouterClient(
    $config[$env]['api_key'],
    $config[$env]
);
```

### 5. Testing

Always write tests for your integration:

```php
use PHPUnit\Framework\TestCase;
use B2BRouter\B2BRouterClient;

class InvoiceIntegrationTest extends TestCase
{
    private $client;
    private $accountId;

    protected function setUp(): void
    {
        $this->client = new B2BRouterClient(
            getenv('B2B_TEST_API_KEY')
        );
        $this->accountId = getenv('B2B_TEST_ACCOUNT_ID');
    }

    public function testCreateAndRetrieveInvoice()
    {
        // Create invoice
        $created = $this->client->invoices->create(
            $this->accountId,
            $this->getTestInvoiceData()
        );

        $this->assertNotEmpty($created['id']);

        // Retrieve invoice
        $retrieved = $this->client->invoices->retrieve($created['id']);

        $this->assertEquals($created['id'], $retrieved['id']);

        // Cleanup
        $this->client->invoices->delete($created['id']);
    }

    private function getTestInvoiceData(): array
    {
        return [
            'invoice' => [
                'number' => 'TEST-' . uniqid(),
                'issue_date' => date('Y-m-d'),
                'currency' => 'EUR',
                // ... more fields
            ]
        ];
    }
}
```

---

## Troubleshooting

### Common Issues

**1. "cURL error 60: SSL certificate problem"**

```bash
# Download CA certificates
sudo mkdir -p /etc/pki/tls/certs
sudo curl -o /etc/pki/tls/certs/ca-bundle.crt https://curl.se/ca/cacert.pem

# Update php.ini
sudo nano /etc/php/8.4/cli/php.ini
# Add: curl.cainfo = /etc/pki/tls/certs/ca-bundle.crt
```

**2. "Xdebug not working"**

```bash
# Check if Xdebug is loaded
php -m | grep xdebug

# Check Xdebug configuration
php -i | grep xdebug

# Enable Xdebug
echo "xdebug.mode=debug" | sudo tee -a /etc/php/8.4/cli/conf.d/20-xdebug.ini
```

**3. "Composer out of memory"**

```bash
# Increase PHP memory limit for Composer
php -d memory_limit=-1 /usr/local/bin/composer install
```

**4. "PHPUnit not found"**

```bash
# Make sure vendor/bin is in your PATH
export PATH="$PATH:./vendor/bin"

# Or use full path
./vendor/bin/phpunit
```

---

## Additional Resources

- [PHP Official Documentation](https://www.php.net/docs.php)
- [Composer Documentation](https://getcomposer.org/doc/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Neovim Documentation](https://neovim.io/doc/)
- [Xdebug Documentation](https://xdebug.org/docs/)
- [B2BRouter API Documentation](https://developer.b2brouter.net)

---

## Quick Command Reference

```bash
# PHP
php --version                      # Check PHP version
php -m                            # List loaded extensions
php -i                            # PHP info
php -l file.php                   # Check syntax
php -r "echo 'hello';"            # Run PHP code

# Composer
composer install                   # Install dependencies
composer update                    # Update dependencies
composer require package/name      # Add dependency
composer dump-autoload            # Regenerate autoloader

# PHPUnit
vendor/bin/phpunit                # Run all tests
vendor/bin/phpunit --filter name  # Run specific test
vendor/bin/phpunit --testdox      # Detailed output
vendor/bin/phpunit --coverage-html cov  # Generate coverage

# Git
git status                        # Check status
git add .                         # Stage all changes
git commit -m "message"           # Commit changes
git push                          # Push to remote

# Neovim
nvim file.php                     # Open file
:Lazy sync                        # Update plugins
:Mason                            # Open Mason
:LspInfo                          # LSP status
:checkhealth                      # Check Neovim health
```

---

**Happy coding! 🚀**
