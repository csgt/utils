# Utils

This package is used as utilities for the csgt packages.

| Package Version | Laravel UI | Cancerbero structure | Menu Table |
| --------------- | ---------- | -------------------- | ---------- |
| 5.0             | no         | names                | es         |
| 5.7             | no         | names                | en         |
| 6.0             | no         | names                | en         |
| 7.0             | yes        | names                | en         |
| 8.0             |            |                      | en         |

To render the menu, use the following snippet. This will auto-generate the required structure needed for the `csgt\menu` package.

```
{!! \Csgt\Utils\Menu::menu() !!}
```
