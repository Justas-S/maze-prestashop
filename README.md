## [MazeTV](tv.maze.lt) prestashop module

### Prerequisites

- Prestashop(minimum version 1.7.6)
- MazeTV client credentials(contact dev@maze.lt)

### Description

This module allows sending data about sold products to MazeTV, which in return will trigger a [Streamlabs](https://streamlabs.com) alert.

### Installation

1. Download the latest [release](https://github.com/Justas-S/maze-prestashop/releases) of the module and upload it to your server `modules/` folder.
2. In Prestashops back office navigated to `Modules -> Module Catalog`, and search for `maze`. ![docs/modules_catalog]()
3. Click `Install` and then `Configure`
4. Enter your authentication ID and key that you received from the maze team, click `Save`.

### Associating streamers with products

This module requires all streamer products to be in a brand dedicated to the streamer.

1. Navigate to `Catalog -> Brands & suppliers`.
2. Add or create a brand that you wish to associate with a streamer
3. Fill in the normal attributes
4. Insert the `MazeTV streamer key` that the streamer provided you
5. Save the brand
6. Start adding products to this brand
