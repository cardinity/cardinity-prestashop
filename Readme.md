# Cardinity Payment Gateway for Prestashop

### Table of Contents  
[<b>Requirements</b>](#Requirements)<br>
[<b>How to install?</b>](#How-to-install)  
       [Method no. 1 (recommended)](#method-no-1-recommended)  
       [Method no. 2](#method-no-2)   
 [<b>Downloads →</b>](#downloads)<br>
 [<b>Having problems?</b>](#having-problems)<br>
 [<b>About us →</b>](#aboutus)<br>     
<a name="headers"/>  

## Installation

### Requirements
• Cardinity account  

First, to avoid any disturbances during installation process, you must change the permissions of ```/modules/``` folder in your Prestashop main directory. 
<br><br>
For <b>Windows:</b> 

1) Open your Prestashop main directory.
2) Right click ```/modules/``` folder and click ```Properties```.
3) Go to "Security" tab and click ```Edit...```
4) Make sure that the user or group under which you operate has "Read & execute" and "Write" checked.

For <b>Linux</b>:
Set permissions on folder ```/modules/``` to 777. You can do so by typing the following command to LINUX terminal: 
```
chmod -R 777 "path to your Prestashop /modules/ folder (for example /data/Prestashop/modules).
```
After the installation, do not forget to change back the permissions of ```/modules/``` folder.  

### How to install?

<b>Click below to watch the tutorial:</b>  
  
[how to integrate cardinity into prestashop](https://www.youtube.com/watch?v=lwKoIbM6kj8)

#### Method no. 1 (recommended)
1) Go to your Prestashop admin panel.
2) Navigate to ```Modules → Module catalog```.
3) At the top right click ```Upload a module```, press ```Select file``` and choose the downloaded Cardinity plugin .zip file.
4) After the installation, click ```Configure```.
5) Enter ```Consumer Key``` and ```Consumer Secret``` which can be found in your Cardinity account.   
<i>Note: if you plan on using the ```External checkout option```, you also need to fill in the ```Project Key``` and ```Project Secret``` fields.</i>
6) Save the changes.

#### Method 2
1) Go to Prestashop main directory.
2) Open ```Modules``` folder and create a new folder named ```Cardinity```.
3) Extract the downloaded Cardinity Payment Module for Prestashop plugin .zip  file.
4) Go to Prestashop admin panel, navigate to ```Modules > Module catalog".
5) Type "Cardinity" in to the search box.
6) Press ```Install```.
7) After the installation, click ```Configure```.
8) Enter ```Consumer Key``` and ```Consumer Secret``` which you can find in your Cardinity account.  
<i>Note: if you plan on using the ```External checkout option```, you also need to fill in the ```Project Key``` and ```Project Secret``` fields.</i>
10) Save the changes.

### Downloads
Find the latest version of this extension here: https://github.com/cardinity/cardinity-prestashop/releases
<details show>
  <summary>For PrestaShop 1.7.x</summary>
  
| Version       | Description                                         |Link        |
| ------------- |-----------------------------------------------------|------------|
| 4.0.6 | Code standard changes for marketplace | <a href="https://github.com/cardinity/cardinity-prestashop/releases/tag/v4.0.6">Download</a> |
</details>

<details show>
  <summary>For PrestaShop 1.4.x - 1.6.x</summary>
  
| Version       | Description                                         |Link        |
| ------------- |-----------------------------------------------------|------------|
| v1.4.4 | More Debug Log | <a href="https://github.com/cardinity/cardinity-prestashop/releases/tag/v1.4.4">Download</a> |
</details>


### Having problems?  

Feel free to contact us regarding any problems that occurred during integration via info@cardinity.com. We will be more than happy to help.

-----

### About us
Cardinity is a licensed payment institution, active in the European Union, registered on VISA Europe and MasterCard International associations to provide <b>e-commerce credit card processing services</b> for online merchants. We operate not only as a <u>payment gateway</u> but also as an <u>acquiring Bank</u>. With over 10 years of experience in providing reliable online payment services, we continue to grow and improve as a perfect payment service solution for your businesses. Cardinity is certified as PCI-DSS level 1 payment service provider and always assures a secure environment for transactions. We assure a safe and cost-effective, all-in-one online payment solution for e-commerce businesses and sole proprietorships.<br>
#### Our features
• Fast application and boarding procedure.   
• Global payments - accept payments in major currencies with credit and debit cards from customers all around the world.   
• Recurring billing for subscription or membership based sales.  
• One-click payments - let your customers purchase with a single click.   
• Mobile payments. Purchases made anywhere on any mobile device.   
• Payment gateway and free merchant account.   
• PCI DSS level 1 compliance and assured security with our enhanced protection measures.   
• Simple and transparent pricing model. Only pay per transaction and receive all the features for free.
### Get started
<a href="https://cardinity.com/sign-up">Click here</a> to sign-up and start accepting credit and debit card payments on your website or <a href="https://cardinity.com/company/contact-us">here</a> to contact us 

___

#### Keywords
payment gateway, credit card payment, online payment, credit card processing, online payment gateway, cardinity for Prestashop.     

  
 [▲ back to top](#Cardinity-Payment-Gateway-for-PrestaShop)
