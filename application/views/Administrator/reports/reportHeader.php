<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Report</title>
    <link rel="stylesheet" href="<?php echo base_url();?>assets/css/bootstrap.min.css">
    <style>
        body{
            padding: 20px!important;
        }
        body, table{
            font-size: 13px;
        }
        table th{
            text-align: center;
        }
    </style>
</head>
<body>
    <?php 
        $branchId = $this->session->userdata('BRANCHid');
        $companyInfo = $this->Billing_model->company_branch_profile($branchId);
    ?>
    <div class="container">
        <div class="row">
            <div class="col-xs-2"><img src="<?php echo base_url();?>uploads/company_profile_thum/<?php echo $companyInfo->Company_Logo_org; ?>" alt="Logo" style="height:80px; width:100%;" /></div>
            <div class="col-xs-8">
                <p style="text-align:center;">বিসমিল্লাহির রাহমানির রাহীম</p>
                <h3 style=" text-align:center; margin:0px; padding:0px;"><?php echo $companyInfo->Company_Name; ?></h3>
                <p style="font-size:10px; text-align:center; margin-bottom: 2px;">এখানে এশিয়ান পেইন্টস এর রং, স্যানিটারি কমোড, বেসিন,প্যান, মটর, গিজার, ট্যাংকি,পাইপ, দরজা, লুকিং গ্লাস, অত্যাধুনিক ঝরনা বাথরুম ফিটিং এবং সকল হার্ডওয়্যার পণ্য খুচরা ও পাইকারি বিক্রয় করা হয়</p>
                <p style="font-size:10px; text-align:center; margin:0px;">মোবাইল :  01831779209 , 01831779217 </p>
                <p style="font-size:12px; text-align:center; margin:0px;">ধামরাই রোড, কাঁচা বাজারের পশ্চিম পাশে, নজিপুর, পত্নীতলা, নওগাঁ। </p>
            </div>
            <div class="col-xs-2"></div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <div style="border-bottom: 4px double #454545;margin-top:7px;margin-bottom:7px;"></div>
            </div>
        </div>
    </div>
</body>
</html>