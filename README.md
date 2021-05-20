# Jokul-PrestaShop-O2O Convenience Store-Plugin

Jokul makes it easy for you accept payments from various channels. Jokul also highly concerned the payment experience for your customers when they are on your store. With this plugin, you can set it up on your PrestaShop website easily and make great payment experience for your customers.

## Requirement
- PrestaShop v1.7 or higher. This plugin is tested with PrestaShop v1.7.7.3
- PHP v7.1 or higher
- MySQL v5.0 or higher
- Jokul account:
    - For testing purpose, please register to the Sandbox environment and retrieve the Client ID & Secret Key. Learn more about the sandbox environment [here](https://jokul.doku.com/docs/docs/getting-started/explore-sandbox)
    - For real transaction, please register to the Production environment and retrieve the Client ID & Secret Key. Learn more about the production registration process [here](https://jokul.doku.com/docs/docs/getting-started/register-user)

## Payment Channels Supported
1. O2O Convenience Store
    - Alfamart O2O

## How to Install
1. Download the plugin from this repository.
2. Extract the plugin and compress folder "jokulo2o" into zip file
3. Login to PrestaShop Admin Panel
5. Go to menu Module > Module Manager
6. Click "Upload a Module" button
7. Upload the jokulo2o.zip that you have compressed
8. You are ready to setup configuration in this plugin!

## Plugin Usage

### Virtual Account Configuration

1. Login to your PrestaShop Admin Panel
2. Click Module > Module Manager
3. You will find "Jokul - O2O ", click "Configure" button
4. Here is the fileds that you required to set:

    ![O2O Configuration](https://i.ibb.co/FqbH7ZB/Screen-Shot-2021-05-20-at-09-49-45.png)

    - **Payment Method Title**: the payment channel name that will shown to the customers. You can use "O2O Convenience Store" for example
    - **Description**: the description of the payment channel that will shown to the customers. 
    - **Environment**: For testing purpose, select Sandbox. For accepting real transactions, select Production
    - **Sandbox Client ID**: Client ID you retrieved from the Sandbox environment Jokul Back Office
    - **Sandbox Shared Key**: Secret Key you retrieved from the Sandbox environment Jokul Back Office
    - **Production Client ID**: Client ID you retrieved from the Production environment Jokul Back Office
    - **Production Shared Key**: Secret Key you retrieved from the Production environment Jokul Back Office
    - **Payment Types**: Select the O2O channel to wish to show to the customers. 
    - **Footer Message**: This will be seen on the payment receipt on the side of the customer when he has paid at the convenience store.
    - **VA Expiry Time (in minutes)**: Input the time that for VA expiration
    - **Notification URL**: Copy this URL and paste the URL into the Jokul Back Office. Learn more about how to setup Notification URL for O2O 
5. Click Save button
6. Now your customer should be able to see the payment channels and you start receiving payments
