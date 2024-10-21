=== Deluxe WP Contracts ===
Contributors: deluxeplugins
Donate link: https://deluxeplugins.com/donate/
Tags: agreements, contracts, digital signature, document signing, e signature
Requires at least: 5.7
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Plugin URI: https://deluxeplugins.com/deluxe-wp-contracts/
Author: Deluxe Plugins
Author URI: https://deluxeplugins.com
Text Domain: deluxe-wp-contracts
Domain Path: /languages

Easily manage custom contracts with automated emails and signatures for seamless digital agreements. Ideal for efficient contract management.

== Description ==
Deluxe WP Contracts allows you to effortlessly create and manage custom contracts with automated email notifications and a streamlined signature process, generating and emailing a PDF for seamless digital agreements. The plugin requires communication with a remote server for its core functionality.

= Features =
- **Automated Contract Creation**: Generate contracts by just filling out a form.
- **Email Notifications**: Automatically send emails upon contract creation, including automated signature handling and sending of signed contracts.
- **Signature Management**: Streamline the process of signing contracts.
- **Remote Server Communication**: Core functionality relies on a secure remote server to generate contracts.
- **Customizable Template**: Create and customize a contract template to fit your needs.
- **User Roles and Permissions**: Assign roles and manage permissions for different users.
- **Activity Logging**: Track and log activities related to contract creation and management.
- **Contract Storage**: Store contracts securely on your own server and access them anytime.

== Installation ==
### From the WorWPress Plugin Repository:
1. Navigate to the 'Plugins' menu in WorWPress.
2. Click 'Add New'.
3. In the search field, type 'Deluxe WP Contracts'.
4. Locate the Deluxe WP Contracts plugin and click 'Install Now'.
5. Once installed, click 'Activate' to enable the plugin.

### Uploading in WorWPress Admin:
1. Download the plugin zip file.
2. Navigate to the 'Plugins' menu in WorWPress.
3. Click 'Add New' and then 'Upload Plugin'.
4. Choose or drag and drop the downloaded zip file and click 'Install Now'.
5. Once installed, click 'Activate' to enable the plugin.

### Manual Installation:
1. Upload the `deluxe-wp-contracts` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WorWPress.
3. Enter your license key and provide consent for data sharing in the Deluxe WP Contracts settings page to enable full functionality.

== Frequently Asked Questions ==
= What data is sent to the remote server? =
User ID, license key, and contract details (including company information, client information, etc.) are sent to the remote server for license verification and contract generation purposes.

= Is contract data stored on the remote server? =
No, contract details are only used to generate the contract and are never stored on the remote server.

= Can I use the plugin without the remote server? =
The core functionality relies on the remote server to generate the pdfs and contracts. Without it, the plugin will have limited functionality and will not be able to generate contracts.

= How do I customize the contract template? =
You can customize the contract template by navigating to the WP Contracts Menu in your WordPress Admin Page and selecting 'Create Contract Template' and setting up your settings. Here, you can edit the contract template to completely fit your needs.

= How do I assign user roles and permissions? =
User roles and permissions can be managed by navigating to the WP Contracts Menu and selecting 'Manage User Roles'. You can assign specific roles to users and control their access to various features of the WP Contracts plugin.

= How is data security handled? =
All data sent to the remote server is encrypted in transit using HTTPS. Contract details are used solely for generating the contract and are immediately deleted afterward. User ID, license key, and tracking information are stored securely on the server.

= Can I store contracts locally? =
Yes, the plugin stores all generated contracts securely within your WorWPress installation. You can access stored contracts from the WP Contracts management pages.

= How do I enable email notifications? =
Email notifications are enabled by default. You can configure the email settings in the WP Contracts settings page, where you can specify the sender's email address and customize the email template.

== Screenshots ==
1. **Creation of a custom contract template - You can create your own contract terms or choose from an example from your industry and modify it to your needs.**
2. **Form to fill to create a new contract.**
3. **Management of generated contracts.**
4. **The link to sign the contract arriving in an email. - You can customize the message body.**
5. **Sign the contract directly through the link in the email.**
6. **Generated PDF with contract information and signatures - All the information submitted through the new contract form will be in the PDF.**
7. **Update existing roles or create new custom roles to prepare to assign them to users.**
8. **Assign existing roles or your custom roles to users to give them custom access to different features of the plugin.**

== Changelog ==
= 1.4 =
* Feature: Added new contract template features.
* Improvement: Enhanced email notification system.

= 1.3 =
* Improvement: Improved user interface for contract management.

= 1.2 =
* Feature: Added automated email notifications.
* Bugfix: Fixed minor bugs related to user roles.

= 1.1 =
* Initial release with basic contract creation and management features.

== Upgrade Notice ==
= 1.4 =
* This update includes new contract template features and improved email notifications. It is recommended to backup your site before upgrading.

== Privacy Policy ==
Visit the following page to view the plugin's [Privacy Policy](https://deluxeplugins.com/deluxe-wp-contracts-privacy-policy/)
