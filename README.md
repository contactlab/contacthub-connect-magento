![Version 1.0.0 beta](https://img.shields.io/badge/version-1.0.0%20beta-0072bc.svg)

# Contacthub Connect for Magento  
### Version 1.0.0
# Installation and Configuration Guide  

----------

## Table of contents

- [Introduction](#Introduction)  
- [Installing the Magento plug-in](#InstallingPlugIn)  
- [Configuring the Magento plug-in](#ConfiguringPlugIn)

<a name="Introduction"/>
## Introduction  

The Contacthub Magento plug-in enables you to automatically send all of the activities that your customers undertake on the e-commerce platform to Contacthub, without the need to write any code. Installing the plug-in is very simple, while configuring it requires just a few minutes.  

<a name="InstallingPlugIn"/>
## Installing the Magento plug-in

To install the plug-in, do the following:  

- Log in to **Magento Admin**, click **System** > **Cache Management** and enable all cache types.  

![Cache Management](image/CacheManagement.png)  

**Cache Management with all cache types enabled**  

- Click **System** > **Tools** > **Compilation** and ensure that **Compiler Status** is set to **Disabled**.  

![Compiler Status](image/Compilation.png)  

**Compiler Status set to disabled**  

- Establish an FTP/SFTP/SSH connection with your website source folder.  

- Upload all of the folders and files in the extension package to the **root** folder of your Magento installation. 

![Magento root](image/MagentoRoot.png)  

**Example of the Magento root folder**  

**Note:**  

You should ensure that you use the **Merge** upload mode of your client to add the new files, and do not replace any existing folders. While **Merge** is often the default setting of FTP/SFTP clients, you should check to be certain. If you are using MacOS, we recommend that you use **Transmit**.  

If you install several extensions from Amasty, they will contain some files that are the same as those in the Base package. You can overwrite these freely, as they are the system files that are used by all of our extensions.  

- Click **System** > **Cache Management** and then click **Flush Cache Storage**.  

   After you have done this, the extension installation is complete.  

- You can now enable compilation, if you need to do so, under **System** > **Tools** > **Compilation**.  

- Log out of **Magento Admin** and then log back in again, to allow Magento to refresh permissions.  

<a name="ConfiguringPlugIn"/>
## Configuring the Magento plug-in

To configure the plug-in, do the following:  

- Log in to **Magento Admin**, then click **System** > **Configuration**.  

![Initial Configuration](image/InitialConfiguration.png)  

**The Magento Admin System options**  

- Click **CONTACTLAB** > **Setup** in the **Magento Configuration** panel, positioned to the left of the screen.  

![Contactlab Setup](image/ConfigGeneral.png)  

**The Contactlab Setup screen, with the General panel displayed**  

- Click **General** and under **Enable**, select **Yes** to enable the Contactlab module.  

- Under **Days before a task is regarded as obsolete**, enter the appropriate number of days.  

- **Enable debug** if required.  

- If you want to receive error notifications by email, click **Error notification email** and complete the fields.  

![Contactlab Setup](image/ConfigErrorMail.png)  

**The Error notification email panel**  

- Ensure that **Magento Cron** is activated. If not, contact your system administrator.  

- Ensure that you are still in the **System** > **Configuration** section and click **CONTACTLAB** > **Contacthub** in the **Magento Configuration** panel.  

- Click **General** and enter or paste the **APIToken**, **API Workspace ID** and **API Node ID** details in the appropriate fields.  

![Contacthub General Settings](image/ChubGeneralSettings.png)  

**The Contacthub General settings**  

- Click **Event track** and do the following:  

    - Enable the customer events that you want to trace  

    - Enter a name for the **Newsletter Campaign**  

    - Select whether you want to track **Abandoned Cart events from non-subscribed customers**  

      If you select **No**, Contacthub only tracks Abandoned Cart events for customers who are subscribed to a newsletter.  

    - Enter the **Minimum number of minutes before sending an Abandoned Cart event**  

    - Under **Maximum number of minutes before sending an Abandoned Cart event**, enter the maximum number of minutes that an Abandoned cart event should be tracked.  

      Required to avoid tracking older events.  

![Contacthub General Settings](image/EventSettings.png)  

**The Event track settings**  

- Click **Cron Export Events Settings** and define:

    - The maximum number of events to export, under **Limit events to export**  

    - The export **Frequency**  

    - The number of **Repeats**  

----------
