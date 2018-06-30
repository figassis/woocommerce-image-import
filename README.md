These scripts allow parsing image names to create woocommerce products into Wordpress.

Requirements
Install rclone: `curl https://rclone.org/install.sh | sudo bash`
Configure rclone by following https://rclone.org/dropbox

The process is as follows:

1. Create a dropbox folder that contains the product images in the following format:
AA-BB-1234[a]_size-color[-brand]-5678[b].jpg : (Portions between [] are optional)

2. The script run/run.sh downloads the entire dropbox directory into /public/import.
It also writes the path to each directory containing a file ready.txt to /import/temp/ready.txt and runs import.php which decodes the images and saves the product data to /public/wp-content/uploads/wpallimport/files/products.csv. (CRON1)

5. The WP All Import plugin then imports this file into the website and deletes it afterward. (CRON2)
