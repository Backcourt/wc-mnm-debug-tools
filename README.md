# Mix and Match Products Debug Tools

### What's This?

Experimental plugin that adds some Tools for dealing with failed update routines. Specicially, we confirm an update routine is required when saving fails with the following error:

![WordPress admin notice that reads "Your changes have not been saved - please wait for the WooCommerce Mix and Match Data Update routine to complete before creating new Mix and Match products or making changes to existing ones.
](https://github.com/user-attachments/assets/d2965efd-a157-4028-827e-39054e6b46b9)

## System Status ##
This plugin will add a JSON representation of the admin Note that is saved in the database in the Mix and Match section of the WooCommerce > Status system status.

![WooCommerce>Status Mix and Match system status now adds a JSON representation of the update admin Note saved in the database](https://github.com/user-attachments/assets/f951fab6-ec50-4b70-ae5b-f6701d4b92cc)

**Note!** If for some reason `is_deleted` is `1` or otherwise _truthy_ the Note will not display, which prevents users from running the update routine required to get saving functioning again.

## Use Existing Tools

Before using the tools enabled by this plugin, you should try deleting the existing Note by navigating to WooCommerce > Status > Tools and locating the "Delete an Inbox Notification" tool. You can search for `mnm` to locate the `wc-mnm-update-db-reminder` note and then once it is selected, click the Delete button.

![WooCommerce>Status>Tools - Delete an Inbox Notification - showing found result of "wc-mnm-update-db-reminder" note and an ensuing button labelled "Delete"](https://github.com/user-attachments/assets/69726c04-6c30-4f12-bc37-c9b450a6b33f)

Mix and Match should now re-spawn the update prompt and hopefully you can proceed by clicking on the update prompt's "Update Database" button.

![WooCommerce Mix and Match database update required admin note with an action button labelled "Update Database"](https://github.com/user-attachments/assets/99d94420-0b1f-4ef5-8f99-01f19160cf75)

### New Tools ##

This plugin adds 2 tools specific to Mix and Match. 

1. Reset the Mix and Match DB version to 2.0 - This was the old way to force the update prompt to re-appear, but it's a hard-reset to 2.0 which might not be appropriate if you still need the 2.0-specific routines (of which there were some major ones)
2. Force run the Mix and Match update routines - Any available updates are added to the Action Scheduler. For example if the DB version is 1.9.x then all 2.0 and 2.2 updates will be scheduled for immedate execution.

![WooCommerce>Status>Tools - two buttons for Mix and Match specific tools. 1. Reset the Mix and Match DB version to 2.0 and 2. Force run the update routines.](https://github.com/user-attachments/assets/0bf6a73f-f8e6-4f31-a189-aaa23554dc9d)

>**Warning**

1. This is provided _as is_ and is only meant for temporary use while debugging.