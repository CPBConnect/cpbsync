# CPB Sync user manual

**Module version:** 1.4.0
**Compatible with:** PrestaShop 8.0 or later (tested on PrestaShop 9.0.0 with PHP 8.4)

CPB Sync connects your shop with your suppliers' catalogues, your ERP or your
own manufacturer website. The module reads the catalogue, translates it into
PrestaShop fields with the rules you decide and creates or updates the products,
without touching anything you have not told it to touch.

This manual explains day-to-day use: it is written for whoever runs the shop,
not for whoever programs the module. If you are looking for the technical
documentation, it is in the `README.md` of the repository.

Two editions share the same code and the same version number:

| | Free version | Paid edition |
| --- | --- | --- |
| CSV catalogues | Yes | Yes |
| XML, JSON and REST API catalogues | No | Yes |
| Nested catalogues and advanced transformations | No (4 transformations) | Yes (19 transformations) |
| Several images per product | Yes | Yes |
| Per-source synchronization options | Yes | Yes |
| Cron scheduling | Yes (4 frequencies) | Yes (10 frequencies) |
| Parallel image downloads | No | Yes |
| Monitoring panel | No | Yes |

Where a feature belongs to the paid edition only, this manual says so.

---

## Contents

1. [Before you start](#1-before-you-start)
2. [Installation](#2-installation)
3. [Updating the module](#3-updating-the-module)
4. [How to reach the module](#4-how-to-reach-the-module)
5. [The sources screen](#5-the-sources-screen)
6. [Creating a source](#6-creating-a-source)
7. [Testing the connection](#7-testing-the-connection)
8. [Mapping the fields](#8-mapping-the-fields)
9. [Transformations](#9-transformations)
10. [Dry Run: seeing the result before touching anything](#10-dry-run-seeing-the-result-before-touching-anything)
11. [Synchronising](#11-synchronising)
12. [Scheduling and cron](#12-scheduling-and-cron)
13. [History](#13-history)
14. [Monitoring (paid edition)](#14-monitoring-paid-edition)
15. [How synchronisation behaves](#15-how-synchronisation-behaves)
16. [Recipes](#16-recipes)
17. [Troubleshooting](#17-troubleshooting)
18. [Frequently asked questions](#18-frequently-asked-questions)
19. [Glossary](#19-glossary)
20. [Annexes](#20-annexes)

---

## 1. Before you start

You need:

- **The catalogue URL** from your supplier: an `http` or `https` link that
  returns the file (`.csv`, `.xml`, `.json`) or the endpoint of an API. A file
  you upload by hand from the module also works.
- **Knowing which column identifies the product** uniquely. It is almost always
  the supplier reference (*SKU*). CPB Sync needs it: without it, it cannot tell
  whether a product is new or already exists in the shop.
- **PrestaShop 8.0 or later** and the PHP version your PrestaShop requires.

A sample product catalogue, in CSV:

```csv
sku,name,description,price,stock,category,brand,image,ean
ABC001,Product One,Product description,25.99,10,Category A,Brand A,https://example.com/image.jpg,1234567890123
ABC002,Product Two,Another description,49.99,5,Category B,Brand B,https://example.com/image2.jpg,1234567890124
```

---

## 2. Installation

1. Download the module ZIP.
2. Open the PrestaShop back office.
3. Go to **Modules → Module Manager**.
4. Click **Upload a module**.
5. Upload the CPB Sync ZIP file.
6. Install it.
7. Open its configuration screen.

Installation creates the module tables and the translation catalogues. If
something fails, the module is **not** marked as installed: try again and, if it
persists, check the write permissions of the module folder.

To uninstall it, use the usual PrestaShop button. Uninstalling deletes the
module tables (sources, mappings, history), so **the products it has created in
the shop stay where they are**: the module does not delete products.

---

## 3. Updating the module

1. Upload the ZIP of the new version as in the installation above.
2. PrestaShop applies the database migrations.
3. **Clear the cache**: **Advanced Parameters → Performance → Clear cache**.

Without step 3 you may see old templates or untranslated texts. If you copy the
files by hand instead of uploading the ZIP, open **Modules → Module Manager** so
that PrestaShop applies the update.

From the command line:

```bash
php bin/console cache:clear --env=prod
```

Run PrestaShop commands as the web server user (`www-data`), not as `root`: the
cache directories they create must belong to the user that serves the shop.

---

## 4. How to reach the module

**Modules → Module Manager → CPB Sync → Configure**.

There is no tab of its own in the menu. Every screen of the module opens from
this page: you navigate with the buttons on each screen.

| Screen | How to get there |
| --- | --- |
| Data sources | It is the home page of the module |
| Add source / Edit | The **Add source** and **Edit** buttons |
| Source preview | The **Test connection** button of a source |
| Map fields | The **Map fields** button of a source |
| Dry Run | The **Run Dry Run** button inside the mapping |
| Synchronization result | The **Run Sync** button inside the mapping |
| Import CSV | The **Import CSV** button |
| History | The **History** button |
| Monitoring (paid) | The **Monitoring** button |

---

## 5. The sources screen

It is the starting point. Each source is shown in its own panel with:

- The **name** of the source, as the title.
- **Type:** `csv`, `xml`, `json` or `rest`.
- **URL:** the address of the catalogue.
- **Frequency:** how often it runs, and **Next run:** with the expected date and
  time. With the *Manual* frequency no next run is shown, because it never runs
  on its own.
- **Status:** **Active** (green) or **Inactive** (grey).

And four buttons:

| Button | What it does |
| --- | --- |
| **Test connection** | Reads the catalogue and shows its columns and its first records, without saving anything. |
| **Map fields** | Opens the mapping table, and from there the Dry Run and the synchronization are launched. |
| **Edit** | Changes the data of the source. |
| **Delete** | Deletes the source and its mapping, after confirming. It **does not delete the products** it has created. |

In the top right corner are **Import CSV**, **History**, **Add source** and,
with the paid edition, **Monitoring**.

If there are still no sources, the screen says so: "You have no sources
configured yet."

---

## 6. Creating a source

Click **Add source**. The form asks for:

| Field | What it is for |
| --- | --- |
| **Name** | What you want to call the source. For example, *Supplier ABC*. |
| **Source type** | How the catalogue is read: `CSV`, `XML`, `JSON` or `REST API (JSON)`. |
| **Source URL** | The address of the catalogue, or the path of the file you have uploaded. |
| **Additional configuration (JSON)** | Optional. Reader settings (see below). |
| **Frequency** | *Manual*, *Hourly*, *Every 6 hours*, *Daily* and, with the paid edition, *Every 15 minutes*, *Every 30 minutes*, *Every 12 hours*, *Every day at a fixed time*, *Once a week* and *Once a month*. |
| **Active source** | Ticked by default. An inactive source is not run by cron. |
| **Synchronization options** | What the synchronization is allowed to write (see below). |

The name and the URL are required, and the URL must be valid (`http` or
`https`). If something does not add up, the form is shown again with a warning
in red: for example, "The source name is required.", "The source URL is
required." or "The additional configuration must be valid JSON.".

> When editing a source, the form heading still says *New source*: it is the
> same form for both things. The data you see is the data of the source you are
> editing.

### 6.1 Additional configuration by catalogue type

The **Additional configuration (JSON)** field is only used by the XML, JSON and
REST readers. It is written in JSON format.

**CSV:** it needs nothing. The module detects on its own whether the columns are
separated by a comma, a semicolon or a tab.

**XML** (paid edition): `record_path` with the XPath path of the element that
repeats. If it is left empty, the module looks for the element that repeats
among siblings.

```json
{"record_path": "/catalog/products/product"}
```

**JSON** (paid edition): `record_path` with the path to the listing, with dots
to go down a level. If it is left empty, the longest list containing the root
object is used.

```json
{"record_path": "data.products"}
```

**REST API** (paid edition): everything is configured here.

```json
{
  "record_path": "data.items",
  "method": "GET",
  "params": {"lang": "es"},
  "headers": {"Accept": "application/json"},
  "auth": {"type": "bearer", "token": "..."},
  "pagination": {
    "type": "page",
    "param": "page",
    "size_param": "per_page",
    "page_size": 100,
    "start": 1,
    "total_path": "meta.total",
    "max_pages": 100
  }
}
```

- `auth.type`: `none`, `bearer` (with `token`), `basic` (with `username` and
  `password`) or `header` (with `header` and `value`).
- `pagination.type`: `none`, `page` (with `param`, `size_param` and `start`) or
  `offset` (with `offset_param` and `limit_param`).
- `total_path` avoids downloading the whole catalogue just to count it.
- `page_size` accepts up to 1000 records per page and `max_pages` up to 1000
  pages: they are the caps that protect the shop from an API that never ends.

### 6.2 Synchronization options

Each source decides what is written into PrestaShop. The four options are
unticked by default, and with none of them ticked the synchronization behaves as
always: it updates everything you have mapped.

| Option | What it does |
| --- | --- |
| **Only create new products** | Products that already exist are left untouched, even if the catalogue has changed. They count as *Skipped*. |
| **Only fill empty fields** | The values the product already has are respected: useful when the descriptions or the prices are edited by hand. |
| **Do not synchronise stock** | The quantity in the shop is left as it is. |
| **Do not import images** | The images the product already has are kept and no new one is downloaded. No download is prepared either, so no network time is wasted. |

Details worth knowing:

- **Only fill empty fields** looks at the reference, the name, the description,
  the price and the EAN. Stock has its own option because an empty stock in a
  catalogue usually means "sold out", and filling it would empty the quantity in
  the shop.
- For the price, a value of `0` counts as empty.
- With **Do not import images**, if you also change the image mapping, the
  product images stay as they were until you untick the option.

The options are saved with the source and affect manual synchronization, file
imports and cron alike.

---

## 7. Testing the connection

The **Test connection** button reads the catalogue and opens the **Source
preview**:

- A green notice: "Connection successful." with the **number of records
  found**.
- **Detected columns**: the list of columns the reader has found. These are the
  names that are mapped later.
- **First records**: a table with the **first 5** records of the catalogue, with
  all their columns.

It saves nothing and touches no product. It is there to check that the URL
responds and that the catalogue is read correctly, and to copy the exact column
names before mapping.

If the connection fails, you go back to the list with a notice such as "Could
not connect to the source: …". The usual causes are in
[Troubleshooting](#17-troubleshooting).

---

## 8. Mapping the fields

Click **Map fields** on the source. The screen shows a notice — "Assign each
source field to the matching PrestaShop field." — and a table with **one row per
catalogue column** and three columns:

| Column | What is chosen |
| --- | --- |
| **Supplier field** | The name of the catalogue column. It is not edited. |
| **PrestaShop field** | Which shop field it corresponds to. |
| **Transformation** | How that value is cleaned or converted before it is saved. |

### 8.1 Available PrestaShop fields

In **PrestaShop field** you can choose:

| Field | What it is |
| --- | --- |
| `reference` | The product reference. **It is required**: it identifies the product. |
| `name` | The name. |
| `description` | The description. It accepts HTML. |
| `price` | The price (excluding VAT, as PrestaShop stores it). |
| `quantity` | The stock. |
| `category` | The category, by its name. |
| `manufacturer` | The manufacturer or the brand, by its name. |
| `image` | The image, or several images. |
| `ean13` | The EAN-13 barcode. |
| *-- Do not map --* | That catalogue column is ignored. |

Rules the module applies:

- **Each PrestaShop field can be assigned only once.** If you repeat a target,
  when saving you will see "The PrestaShop field "…" is assigned more than
  once.".
- If you do not map any column to `reference`, the mapping cannot be saved:
  "You must map a supplier field to reference.".
- These nine fields are **all** that can be mapped. The module does not write
  weight, cost price, taxes, SEO fields, tags, combinations or second
  categories: only one category and one manufacturer per product.
- Names, descriptions and the rest of the texts are saved in the **default
  language of the shop**.
- The **friendly URL** is not mapped: it is generated from the name and only
  when the product does not have one yet, so as not to overwrite the addresses
  you have already customised.

### 8.2 Automatic suggestions

While the source **has no mapping saved**, the module suggests a target for the
usual column names, in English and in Spanish: `sku`, `referencia`, `name`,
`nombre`, `precio`, `price`, `stock`, `cantidad`, `marca`, `brand`, `imagen`,
`image`, `ean`, `barcode`… It also understands nested columns: `price.value`
suggests `price` and `images.0` suggests `image`.

The suggestions can be changed before saving. As soon as you save a mapping, the
module stops suggesting: that way it never saves a mapping you have not chosen
yourself.

### 8.3 Choosing transformations

In the **Transformation** column each row offers only the transformations that
make sense for the chosen target field (for example, *Normalize price* only
appears in the rows that point to `price`). When you choose one, its
configuration fields unfold just below, and what you type is saved with the
mapping.

The details of each one are in [Transformations](#9-transformations).

### 8.4 Saving

Click **Save mapping**. If all goes well you will see "The mapping was saved
successfully.". The other buttons on the screen are **Back** (to the source
list), **Run Dry Run** and **Run Sync**.

You do not need to save before launching the Dry Run or the synchronization, but
you do need a saved mapping: if there is none, you will see "The source has no
mapping configured.".

---

## 9. Transformations

A transformation cleans the value that comes from the catalogue before writing
it. The module includes four in the free version and fifteen more in the paid
edition.

| Transformation | Edition | What it is for | Configuration |
| --- | --- | --- | --- |
| **Normalize price** | Free | Removes currency symbols and fixes the separators (`1.234,56`, `1,234.56`, `10,50 €`, `10.50 EUR`). | *Decimal separator* and *Thousands separator*; when blank they are detected on their own. |
| **Normalize stock** | Free | Turns texts such as `3 units` or `more than 3` into a number. | — |
| **Normalize text** | Free | Removes leftover spaces. For names, descriptions, brands and categories. | — |
| **Replace text** | Free | Replaces one text with another; when blank, it removes it. | *Search* and *Replace with*. |
| **Map values** | Paid | Equivalence table, one rule per line with `origen=destino` (for example `En stock=7`). | *Value map*. |
| **Default value** | Paid | Uses a value when the supplier sends nothing. | *Default value*. |
| **Use another field** | Paid | If the field comes empty, it uses another one from the same row. | *Fallback fields*, in order of preference. |
| **Join fields** | Paid | Joins several fields (for example `marca` + `nombre`, or several images). | *Fields to join* and *Separator*. |
| **Add prefix or suffix** | Paid | Adds text before or after (for example the supplier code). | *Prefix* and *Suffix*. |
| **Arithmetic** | Paid | Multiplies, divides, adds or subtracts, with rounding. Useful for prices and stock. | *Operation*, *Value* and *Decimal places*. |
| **Extract with a pattern** | Paid | Takes a part of the value with a regular expression. | *Regular expression* and *Capture group*. |
| **Replace with a pattern** | Paid | Replaces a pattern with another text. | *Regular expression* and *Replace with*. |
| **Convert to yes/no** | Paid | Turns a text into `1` or `0`. | *Values that mean yes*. |
| **Shorten text** | Paid | Trims long texts and adds an ellipsis. | *Maximum length* and *Add at the end*. |
| **Build a slug** | Paid | Builds a URL-like text: lower case, no accents. | *Separator*. |
| **Change capitalisation** | Paid | EVERYTHING IN CAPITALS, everything in lower case, Each Word With Capitals or Only the first letter. | *Capitalisation*. |
| **Remove HTML** | Paid | Removes the markup and keeps the text. | — |
| **Take one part** | Paid | Takes one part of a value with a separator, for example `Ropa\|Zapatos`. | *Separator* and *Part number* (from 1). |
| **Keep only digits** | Paid | From `123-456` it takes `123456`. For EANs and references. | — |

Examples:

| I want… | Target field | Transformation | Configuration |
| --- | --- | --- | --- |
| A price that comes as `1.234,56 €` | `price` | Normalize price | (blank) |
| A stock that comes as `más de 3` | `quantity` | Normalize stock | — |
| To add 21% VAT to a price without VAT | `price` | Arithmetic | Operation `Multiply`, Value `1.21` |
| To turn `En stock` / `Agotado` into stock | `quantity` | Map values | `En stock=10` and `Agotado=0` |
| An EAN that comes as `123-456-789-012-3` | `ean13` | Keep only digits | — |
| To join several nested images | `image` | Join fields | Fields `images.1,images.2`, Separator `,` |

If the configuration is not valid, the mapping is not saved and the reason is
explained (for example, "You must provide the text to replace for the "…"
field.").

---

## 10. Dry Run: seeing the result before touching anything

The **Dry Run** reads the source, applies the mapping and the transformations
and shows you the result **without modifying any product** or leaving a trace in
the history.

It is launched with **Run Dry Run** from the mapping screen. The screen shows:

- A notice: "The first 5 products from the source are shown." and "No product
  was modified in PrestaShop.".
- A panel for each of those 5 products, with the label **Valid** (green) or
  **With errors** (red). If it has errors, they are listed.
- A table per product with three columns: **PrestaShop field**, **Original**
  (what came in the catalogue) and **Result** (what would be saved, in bold and
  with the **Changed** label when the transformation has modified the value).

It is the quickest way to check a mapping: if you do not like the result, change
the transformation, save and launch it again. At the foot there is a **Back to
mapping** button.

---

## 11. Synchronising

### 11.1 Manual synchronisation

Click **Run Sync** on the mapping screen. The module goes through the whole
catalogue, applies the mapping and creates or updates the products. When it
finishes you will see the **Synchronization result**:

- **Synchronization completed**, and if it has been saved in the history, **Run
  #N** with the note "This synchronization was recorded in the history.".
- Five counters:

| Counter | What it means |
| --- | --- |
| **Total** | Products read from the catalogue. |
| **Created** | New products in PrestaShop. |
| **Updated** | Products that already existed and have changed. |
| **Skipped** | Products that already existed and needed no changes, or that have been skipped by the *Only create new products* option. |
| **Errors** | Products that could not be processed. |

- A table with one row per product: **Reference**, **Status**, **PrestaShop ID**
  and **Detail**. In **Detail** each error is explained, product by product; the
  synchronization **does not stop** because of a product that fails.
- A **Back to mapping** button at the foot.

Manual synchronization processes the whole catalogue in a single request, so it
has no cap on products, but it does depend on PHP's maximum execution time and
on the tab not being closed. For very large catalogues it is better to use cron
(which goes in batches) or the file import, which also advances in batches and
shows the progress.

### 11.2 Importing a file

If your supplier sends you the catalogue by email or you download it by hand,
use **Import CSV** on the sources screen:

1. Choose the **Source** (the mapping that will be applied).
2. Choose the **CSV file**.
3. Click **Upload and start import**.

The module uploads the file, counts the records and processes it **in batches**,
showing the progress: "Processing import", the bar with the percentage, the
`processed / total` counter, the accumulated **Successful:** and **Errors:**.
When it finishes: "Import completed successfully." or "The import finished with
errors.".

Useful details:

- The paid edition also accepts `.xml` and `.json`; without it, `.csv`.
- Do not close the tab while the bar is moving: the import is done through
  successive calls from the browser.
- The temporary file is deleted from the server when it finishes.

### 11.3 Large catalogues

The module is designed for catalogues of thousands of products:

- Products **without changes are detected and skipped**, so a re-synchronisation
  of a catalogue that has not changed is very fast and rewrites nothing.
- Each read goes in batches (50 records in CSV, 200 in XML and JSON, 100 in
  REST).
- The images of a batch are prepared before processing it, and with the paid
  edition they are downloaded in parallel (several at a time).

---

## 12. Scheduling and cron

The frequency of a source says how often it is due to run. For that to happen,
the cron command has to be scheduled on the server: the module does not have a
"scheduler" of its own.

### 12.1 Frequencies

| Frequency | Edition |
| --- | --- |
| Manual | Free |
| Hourly | Free |
| Every 6 hours | Free |
| Daily | Free |
| Every 15 minutes | Paid |
| Every 30 minutes | Paid |
| Every 12 hours | Paid |
| Every day at a fixed time | Paid |
| Once a week | Paid |
| Once a month | Paid |

The calendar frequencies ask for their data in the form itself: the time (in
`HH:MM` format), the day of the week or the day of the month. If the month is
shorter than the chosen day, the last day is used.

### 12.2 The command

The script **only works from the command line**: if it is called over HTTP it
returns `403 Forbidden`. There is no cron URL or security token, and none is
needed: it runs inside the server.

```bash
php /ruta/a/prestashop/modules/cpbsync/cron.php
```

A `crontab` line to run it every hour:

```bash
0 * * * * php /ruta/a/prestashop/modules/cpbsync/cron.php
```

You can schedule it **more often** than you need (for example every 5 minutes):
each source only runs when it is due according to its frequency. If cron does
not run, there is no automatic synchronization: the source list will tell you
when each one would be due.

The command output is a summary per source:

```text
Source 3: total=120 created=4 updated=2 skipped=114 errors=0
```

If a run is already in progress, the command warns ("CPB Sync cron is already
running.") and finishes without doing anything: two simultaneous crons do not
step on each other.

### 12.3 When each source is due

- Interval frequencies (*Hourly*, *Every 6 hours*, *Every 15 minutes*…) count
  from the **last cron run** of that source. A manual synchronization does
  **not** reset the counter.
- Calendar frequencies (*Every day at a fixed time*, *Once a week*, *Once a
  month*) run **once per period**. If cron is down for two days, the runs that
  were missed are not recovered: they do not pile up.
- The time is read in the **time zone of the shop**, not in the server's.
- Only **active** sources with a frequency other than *Manual* are run.

---

## 13. History

The **History** button opens the list of the **50 most recent runs**:

| Column | What it shows |
| --- | --- |
| **Date** | When it ran. |
| **Source** | Which source, or "Deleted source" if it no longer exists. |
| **Total** | Products read. |
| **Created** | New products. |
| **Updated** | Modified products. |
| **Unchanged** | Skipped products. |
| **Errors** | Products with an error. |
| **Duration** | How long it took, in seconds. |
| **Status** | **Success** (no errors), **Warning** (some errors) or **Error** (all failed). |
| **View detail** | Opens the detail of that run. |

The detail (**Synchronization detail**) shows the date, the source, the
**duration**, the maximum **memory** used and the breakdown by phases —**Reading
the source**, **Applying the mapping** and **Writing products**—, the five
counters and the **Processed products** table with the reference, the status,
the ID and the errors of each one. At the foot, **Back to history**.

Two things worth knowing:

- The history stores the summary of each run and a **sample of 200 products**.
  If the catalogue is larger, the screen itself warns you: "Only the first 200
  of N records are stored. The rest are summarised in the counters above.".
- The **Dry Run does not appear** in the history, because it modifies nothing.

---

## 14. Monitoring (paid edition)

The **Monitoring** button summarises the activity of the shop over a period:

- Periods: **Last 24 hours**, **Last 7 days** (default), **Last 30 days** and
  **Last 90 days**.
- Counters for the period: **Runs**, **Products processed**, **Errors**,
  **Average duration**, **Created**, **Updated**, **Unchanged** and **Longest
  run**, with the date of the **Last run:** (or "Never").
- **Most frequent errors**: the five messages that repeat the most, with the
  number of **Times**. They are calculated over the most recent runs with
  errors, not only over the chosen period.
- **History storage**: how many runs are stored and how much space they take.
  The **Delete old runs** button deletes those older than the retention period
  (30 days) after confirmation.
- **Recent runs**: the 20 most recent, with the **Trigger** column that
  distinguishes `manual` runs from `cron` runs, and a **Detail** button.

The history is kept small on purpose: it stores the summary of each run and a
sample of products, so an hourly cron on a large catalogue does not fill the
database.

---

## 15. How synchronisation behaves

This section summarises what the module does with each product, so that there
are no surprises.

**New products.** They are created with the reference and the name that come
from the catalogue, and they are left **active**. If the reference or the name
is missing, the product is not created and the error is reported.

**Existing products.** The module looks for the product by its **reference**. If
none of the mapped fields has changed (name, description, price, stock, EAN,
manufacturer and category), the product counts as **Unchanged** and is not
touched: that is what makes a re-synchronisation fast.

**Stock.** If you map `quantity`, the quantity in the shop is overwritten with
the catalogue's when it changes. If you do not want it touched, tick **Do not
synchronise stock**.

**Price.** It is stored exactly as PrestaShop writes it (excluding VAT). If your
catalogue brings the price including VAT or with a currency symbol, use
*Normalize price* and, if needed, *Arithmetic*.

**Categories and manufacturers.** They are looked up **by name** in the default
language of the shop and, if they do not exist, they are created. A product can
have one category and one manufacturer assigned by the catalogue; the categories
the product already had are kept.

**Images.** The image field accepts **several URLs** in a single value,
separated by commas, semicolons, pipes or line breaks, up to 20 per product.
Only the **first** one becomes the cover. The module:

- Downloads the new images **before** deleting the previous ones: if the
  supplier does not respond, the product keeps the ones it had.
- Replaces all the product images when the catalogue URL changes.
- A broken link does not prevent the rest: if at least one image is downloaded,
  the product is saved.
- Accepts JPG, PNG, GIF and WebP of up to 5 MB per image. It follows up to 3
  redirects (CDNs usually use them).
- Does not download images from private addresses, for security.

**Reference and friendly URL.** The reference is the key: if you change it in
the catalogue, the module will create a new product instead of updating the
previous one. The friendly URL is generated from the name only when it is
missing.

---

## 16. Recipes

**I only want to update prices and stock.** Map only `reference`, `price` and
`quantity`. Tick **Do not import images** if you do not want the module to touch
the photos, and leave the other fields unmapped: what is not mapped is not
written.

**I have the descriptions written by hand and I do not want to lose them.**
Tick **Only fill empty fields**: the module will only write where there is
nothing. It is also the option for not overwriting the prices you review by
hand.

**The supplier changes the prices every day and I want to control them.** Do not
map `price` and map everything else; or tick **Only fill empty fields** so that
the price you already have is respected.

**I want to only add the new products.** Tick **Only create new products**: the
ones that already exist are left as they are.

**The column names do not match PrestaShop's.** Use the transformations and the
mapping: for example *Map values* to turn `En stock` into `10`, or *Join fields*
to join brand and name.

**I have a large catalogue and I only want it to run at night.** Set the *Every
day at a fixed time* frequency (paid edition) to 03:00 and schedule the server
cron every hour or every 15 minutes. The next day's synchronization will be very
fast because products without changes are skipped.

**I want to test a large catalogue without risking anything.** Open the source,
launch **Run Dry Run** and look at the first five products: you will see the
original value and the final result, field by field. When it convinces you,
launch **Run Sync**.

---

## 17. Troubleshooting

| What you see | What is happening | What to do |
| --- | --- | --- |
| "Could not connect to the source: …" | The URL does not respond, takes too long or returns an error. | Check the URL in the browser; if the supplier asks for a user and a password, use a REST source with `auth`. |
| "The source contains no records." | The catalogue has been read but the list of products has not been found. | In XML and JSON, check `record_path` (for example `data.products`). Use **Test connection** to see the columns it detects. |
| "The source has no mapping configured." | The Sync or the Dry Run has been launched without saving the mapping. | Go back to **Map fields**, assign at least `reference` and click **Save mapping**. |
| "You must map a supplier field to reference." | No column is assigned to `reference`. | Assign to `reference` the column that identifies the product (usually the SKU). |
| "The PrestaShop field "…" is assigned more than once." | Two columns point to the same field. | Leave only one; the other one, in *-- Do not map --*. |
| "The price format is not valid." / "The stock format is not valid." | The catalogue value brings text or symbols the transformation has not cleaned. | Add *Normalize price* or *Normalize stock* to that column. |
| "The ean13 field must contain exactly 13 digits." or a similar notice in English | The EAN does not have 13 digits after the transformation. | Use *Keep only digits*; if the supplier does not give a valid EAN, leave that column unmapped. |
| A product appears with the status **Error** and its detail explains the reason | The product has not passed validation (the name is missing, the price is not a number, the quantity is not an integer…) or PrestaShop has not been able to save it. | Fix the data at the source or with a transformation. The other products have been processed all the same. |
| "The image could not be downloaded." | The image link does not respond or the server blocks it. | Check the link; if the images come from a CDN, check that they are `https` and reachable from the server. A broken link does not prevent the other images. |
| "The image exceeds the maximum allowed size of 5 MB." | The image is too large. | Use a smaller version; catalogue photos should ideally not exceed 1-2 MB. |
| The images of the product are not updated | The image URL has not changed, or the **Do not import images** option is ticked. | Check the option on the source and that the catalogue brings another URL. |
| The stock does not change | The **Do not synchronise stock** option is ticked, or `quantity` is not mapped. | Check the option and the mapping. |
| "The source has been re-synchronised and nothing has changed" | It is what is expected: products without changes are skipped. | If you expected changes, check that the catalogue brings them and that they are mapped. |
| No back office page loads and a cache error appears | The cache was left owned by `root` because console commands were run as root. | Give the ownership back to `www-data`: `chown -R www-data:www-data var/` and clear the cache. |
| "The file type is not allowed." | The imported file is not of an accepted type. | Use `.csv` (or `.xml`/`.json` with the paid edition). |
| Cron runs nothing | The source is inactive, its frequency is *Manual*, or the command is not scheduled. | Activate the source, choose a frequency and check the `crontab` line. Remember that the script only works from the command line. |

---

## 18. Frequently asked questions

**Are the products that disappear from the catalogue deleted?**
No. CPB Sync creates and updates, it never deletes products. If a product stops
coming in the catalogue, it stays in the shop exactly as it was.

**Can I map the same column twice?**
Not to the same target: each PrestaShop field accepts a single column, and if
two point to the same place the mapping is not saved. What you can do is map the
same catalogue column to two different fields (for example, a column
`nombre_completo` to `name` and to `manufacturer`).

**Can products be created in several languages?**
No. The texts are written in the default language of the shop.

**And several catalogue languages or several shops?**
The module does not distinguish shops: the settings belong to the installation.
Only the cover of the images is per shop, as in PrestaShop.

**What happens if the catalogue repeats a reference?**
The last row read for that reference is processed.

**How long does a synchronization take?**
It depends on the catalogue and on the image provider. As a reference measured in
PrestaShop 9.0.0 with 200 products: creating products ~110 ms each; a
re-synchronisation without changes ~0.5 ms per product; downloading a public
image ~0.5 s (less with the parallel download of the paid edition).

**Can I launch the synchronization while cron is running?**
Yes, the manual run does not wait for cron, but it is better not to: two
synchronizations at once over the same products work twice over (cron does
protect itself from itself, not from a manual run).

**Does the module store the whole catalogue?**
No. It stores the source, the mapping, a fingerprint of the synchronised values
to detect changes, and the history (summary plus a sample of products).

**Can I edit a synchronised product without the module overwriting it?**
Yes: tick **Only fill empty fields**, which respects what is already written, or
leave unmapped the fields you want to keep.

---

## 19. Glossary

| Term | What it is |
| --- | --- |
| **Source** | An external catalogue and its settings: URL, type, frequency and options. |
| **Reader** | The component that understands each format (CSV, XML, JSON, REST). |
| **Mapping** | The table that says which catalogue column goes to which PrestaShop field. |
| **Transformation** | The rule that cleans or converts a value before saving it. |
| **Dry Run** | Simulation: it applies the mapping and shows the result without touching the shop. |
| **Batch** | A block of records (50, 100 or 200) processed in one go. |
| **Fingerprint** | The value the module stores for each synchronised field, to know whether it has changed. |
| **Cover** | The main image of the product. Only the first imported image is the cover. |
| **Cron** | The server scheduler, which runs the module command when it is due. |
| **Reference** | The identifier of the product in the catalogue (`reference` in PrestaShop). |

---

## 20. Annexes

### Annex A. PrestaShop fields that can be mapped

`reference`, `name`, `description`, `price`, `quantity`, `category`,
`manufacturer`, `image`, `ean13`.

None else. The module does not write weight, cost price, taxes, tags,
combinations, SEO fields or second categories.

### Annex B. System limits

| Limit | Value |
| --- | --- |
| Images per product | 20 (extra URLs are ignored) |
| Size per image | 5 MB |
| Image formats | JPG, PNG, GIF and WebP |
| Redirects followed (source and images) | 3 |
| Records per batch | 50 in CSV, 200 in XML and JSON, 100 in REST |
| Products shown by the Dry Run and the preview | 5 |
| Products stored in the history of each run | 200 |
| Runs shown by the history | 50 |
| Recent runs in monitoring | 20 |
| History retention | 30 days |
| Pages per REST API | 100 by default, 1000 at most |
| Records per page in REST | 100 by default, 1000 at most |
| Import file size | Set by PHP (`upload_max_filesize`) |

### Annex C. Statuses and counters

| Where it appears | Values |
| --- | --- |
| Source status | **Active**, **Inactive** |
| Status of a run | **Success**, **Warning**, **Error** |
| Status of a product | **Created**, **Updated**, **Skipped**, **Errors** |
| Status of a product in the Dry Run | **Valid**, **With errors** |
| Trigger of a run | `manual` (by hand or import), `cron` |

### Annex D. Module messages

The messages you will see most often, exactly as they are shown in English:

| Message | When it appears |
| --- | --- |
| The source name is required. | A source is saved without a name. |
| The source URL is required. / The source URL is not valid. | The URL is missing or does not have a correct format. |
| The additional configuration must be valid JSON. | The JSON of `config` is misspelled. |
| The time is not valid. Use the HH:MM format. | A calendar frequency with a wrongly set time. |
| You must map a supplier field to reference. | The mapping does not take any column to `reference`. |
| The PrestaShop field "…" is assigned more than once. | Two columns point to the same target. |
| The mapping was saved successfully. | The mapping has been saved. |
| The source has no mapping configured. | Sync or Dry Run without a saved mapping. |
| The source contains no records. | The catalogue has been read but no products have been found. |
| Synchronization completed. | The synchronization has finished. |
| This synchronization was recorded in the history. | The run has been stored, with its number. |
| Only the first 200 of N records are stored. The rest are summarised in the counters above. | The catalogue had more products than the limit of the history. |
| Import completed successfully. / The import finished with errors. | End of a file import. |
| The file type is not allowed. | A file of an unsupported type has been uploaded. |

The validation errors of a specific product (an empty reference or name, a price
that is not a number, a quantity that is not an integer, an EAN that does not
have 13 digits, an image that is not a URL) are written by the product validator
and today they arrive **untranslated**, in English, both in the synchronization
result and in the history detail. They are fixed with the mapping or with a
transformation; in practical terms the message is the same in both languages: it
indicates the product field that has to be fixed.

---

*CPB Sync — Simplify the synchronisation of your catalogue in PrestaShop.*
