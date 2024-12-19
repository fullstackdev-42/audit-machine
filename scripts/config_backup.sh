cd /mnt/ITAM_Encrypted/html/asbdc/
sudo cp /mnt/ITAM_Encrypted/html/configs/asbdc/auditprotocol/config.php auditprotocol/config.php
sudo cp /mnt/ITAM_Encrypted/html/configs/asbdc/portal/config.php portal/config.php
cd  /mnt/ITAM_Encrypted/html/asbdc/portal
cd data
sudo rm data_test.php
cd ..
sudo rm data -d 
cd /mnt/ITAM_Encrypted/html/asbdc/portal/
sudo mv /mnt/ITAM_Encrypted/html/configs/asbdc/portal/data/ .
sudo mv /mnt/ITAM_Encrypted/html/configs/asbdc/portal/template_output/ .
cd /mnt/ITAM_Encrypted/html/asbdc/auditprotocol/
sudo rm -r templates
sudo rm data -d 
sudo mv /mnt/ITAM_Encrypted/html/configs/asbdc/auditprotocol/data/ .
sudo mv /mnt/ITAM_Encrypted/html/configs/asbdc/auditprotocol/templates/ .
cd /mnt/ITAM_Encrypted/html/asbdc/policymachine/
sudo mv /mnt/ITAM_Encrypted/html/configs/asbdc/policymachine/customerdocuments/ .