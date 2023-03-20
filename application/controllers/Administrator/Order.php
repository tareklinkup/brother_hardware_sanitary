<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Order extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->sbrunch = $this->session->userdata('BRANCHid');
        $access = $this->session->userdata('userId');
         if($access == '' ){
            redirect("Login");
        }
        $this->load->model('Billing_model');
        $this->load->library('cart');
        $this->load->model('Model_table', "mt", TRUE);
        $this->load->helper('form');
        $this->load->model('SMS_model', 'sms', true);
    }
    
    public function index($serviceOrProduct = 'product')  {
        $access = $this->mt->userAccess();
        if(!$access){
            redirect(base_url());
        }
        $this->cart->destroy();
        $this->session->unset_userdata('cheque');
        $data['title'] = "Product Order";
        
        $invoice = $this->mt->generateSalesInvoice();

        $data['isService'] = $serviceOrProduct == 'product' ? 'false' : 'true';
        $data['salesId'] = 0;
        $data['invoice'] = $invoice;
        $data['content'] = $this->load->view('Administrator/order/product_order', $data, TRUE);
        $this->load->view('Administrator/index', $data);
    }
    

    public function addOrder(){
        $res = ['success'=>false, 'message'=>''];
        try{
            $data = json_decode($this->input->raw_input_stream);

            $invoice = $data->sales->invoiceNo;
            $invoiceCount = $this->db->query("select * from tbl_salesmaster where SaleMaster_InvoiceNo = ?", $invoice)->num_rows();
            if($invoiceCount != 0){
                $invoice = $this->mt->generateSalesInvoice();
            }

            $customerId = $data->sales->customerId;
            if(isset($data->customer)){
                $customer = (array)$data->customer;
                unset($customer['Customer_SlNo']);
                unset($customer['display_name']);
                $customer['Customer_Code'] = $this->mt->generateCustomerCode();
                $customer['status'] = 'a';
                $customer['AddBy'] = $this->session->userdata("FullName");
                $customer['AddTime'] = date("Y-m-d H:i:s");
                $customer['Customer_brunchid'] = $this->session->userdata("BRANCHid");

                $this->db->insert('tbl_customer', $customer);
                $customerId = $this->db->insert_id();
            }

            $sales = array(
                'SaleMaster_InvoiceNo' => $invoice,
                'SalseCustomer_IDNo' => $customerId,
                'employee_id' => $data->sales->employeeId,
                'SaleMaster_SaleDate' => $data->sales->salesDate,
                'SaleMaster_SaleType' => $data->sales->salesType,
                'SaleMaster_TotalSaleAmount' => $data->sales->total,
                'SaleMaster_TotalDiscountAmount' => $data->sales->discount,
                'SaleMaster_TaxAmount' => $data->sales->vat,
                'SaleMaster_Freight' => $data->sales->transportCost,
                'SaleMaster_SubTotalAmount' => $data->sales->subTotal,
                'SaleMaster_PaidAmount' => $data->sales->paid,
                'SaleMaster_DueAmount' => $data->sales->due,
                'SaleMaster_Previous_Due' => $data->sales->previousDue,
                'SaleMaster_Description' => $data->sales->note,
                'Status' => 'p',
                'is_service' => $data->sales->isService,
                'is_order' => 'true',
                "AddBy" => $this->session->userdata("FullName"),
                'AddTime' => date("Y-m-d H:i:s"),
                'SaleMaster_branchid' => $this->session->userdata("BRANCHid")
            );
    
            $this->db->insert('tbl_salesmaster', $sales);
            
            $salesId = $this->db->insert_id();
    
            foreach($data->cart as $cartProduct){
                $saleDetails = array(
                    'SaleMaster_IDNo' => $salesId,
                    'Product_IDNo' => $cartProduct->productId,
                    'SaleDetails_TotalQuantity' => $cartProduct->quantity,
                    'Purchase_Rate' => $cartProduct->purchaseRate,
                    'SaleDetails_Rate' => $cartProduct->salesRate,
                    'SaleDetails_Tax' => $cartProduct->vat,
                    'SaleDetails_TotalAmount' => $cartProduct->total,
                    'Status' => 'p',
                    'AddBy' => $this->session->userdata("FullName"),
                    'AddTime' => date('Y-m-d H:i:s'),
                    'SaleDetails_BranchId' => $this->session->userdata('BRANCHid')
                );
    
                $this->db->insert('tbl_saledetails', $saleDetails);
    
                //update stock
                // $this->db->query("
                //     update tbl_currentinventory 
                //     set sales_quantity = sales_quantity + ? 
                //     where product_id = ?
                //     and branch_id = ?
                // ", [$cartProduct->quantity, $cartProduct->productId, $this->session->userdata('BRANCHid')]);
            }
            $currentDue = $data->sales->previousDue + ($data->sales->total - $data->sales->paid);
            //Send sms
            $customerInfo = $this->db->query("select * from tbl_customer where Customer_SlNo = ?", $customerId)->row();
            $sendToName = $customerInfo->owner_name != '' ? $customerInfo->owner_name : $customerInfo->Customer_Name;
            $currency = $this->session->userdata('Currency_Name');

            $message = "Dear {$sendToName},\nYour bill is {$currency} {$data->sales->total}. Received {$currency} {$data->sales->paid} and current due is {$currency} {$currentDue} for invoice {$invoice}";
            $recipient = $customerInfo->Customer_Mobile;
            $this->sms->sendSms($recipient, $message);
    
            $res = ['success'=>true, 'message'=>'Order Success', 'salesId'=>$salesId];

        } catch (Exception $ex){
            $res = ['success'=>false, 'message'=>$ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function OrderEdit($productOrService, $salesId){
        $data['title'] = "Order update";
        $sales = $this->db->query("select * from tbl_salesmaster where SaleMaster_SlNo = ?", $salesId)->row();
        $data['isService'] = $productOrService == 'product' ? 'false' : 'true';
        $data['salesId'] = $salesId;
        $data['invoice'] = $sales->SaleMaster_InvoiceNo;
        $data['content'] = $this->load->view('Administrator/order/product_order', $data, TRUE);
        $this->load->view('Administrator/index', $data);
    }


    public function updateOrder(){
        $res = ['success'=>false, 'message'=>''];
        try{
            $data = json_decode($this->input->raw_input_stream);
            $salesId = $data->sales->salesId;

            if(isset($data->customer)){
                $customer = (array)$data->customer;
                unset($customer['Customer_SlNo']);
                unset($customer['display_name']);
                $customer['UpdateBy'] = $this->session->userdata("FullName");
                $customer['UpdateTime'] = date("Y-m-d H:i:s");

                $this->db->where('Customer_SlNo', $data->sales->customerId)->update('tbl_customer', $customer);
            }

            $sales = array(
                'SalseCustomer_IDNo' => $data->sales->customerId,
                'employee_id' => $data->sales->employeeId,
                'SaleMaster_SaleDate' => $data->sales->salesDate,
                'SaleMaster_SaleType' => $data->sales->salesType,
                'SaleMaster_TotalSaleAmount' => $data->sales->total,
                'SaleMaster_TotalDiscountAmount' => $data->sales->discount,
                'SaleMaster_TaxAmount' => $data->sales->vat,
                'SaleMaster_Freight' => $data->sales->transportCost,
                'SaleMaster_SubTotalAmount' => $data->sales->subTotal,
                'SaleMaster_PaidAmount' => $data->sales->paid,
                'SaleMaster_DueAmount' => $data->sales->due,
                'SaleMaster_Previous_Due' => $data->sales->previousDue,
                'SaleMaster_Description' => $data->sales->note,
                "UpdateBy" => $this->session->userdata("FullName"),
                'UpdateTime' => date("Y-m-d H:i:s"),
                "SaleMaster_branchid" => $this->session->userdata("BRANCHid")
            );
    
            $this->db->where('SaleMaster_SlNo', $salesId);
            $this->db->update('tbl_salesmaster', $sales);
            
            $currentSaleDetails = $this->db->query("select * from tbl_saledetails where SaleMaster_IDNo = ?", $salesId)->result();
            $this->db->query("delete from tbl_saledetails where SaleMaster_IDNo = ?", $salesId);

            foreach($currentSaleDetails as $product){
                $this->db->query("
                    update tbl_currentinventory 
                    set sales_quantity = sales_quantity - ? 
                    where product_id = ?
                    and branch_id = ?
                ", [$product->SaleDetails_TotalQuantity, $product->Product_IDNo, $this->session->userdata('BRANCHid')]);
            }
    
            foreach($data->cart as $cartProduct){
                $saleDetails = array(
                    'SaleMaster_IDNo' => $salesId,
                    'Product_IDNo' => $cartProduct->productId,
                    'SaleDetails_TotalQuantity' => $cartProduct->quantity,
                    'Purchase_Rate' => $cartProduct->purchaseRate,
                    'SaleDetails_Rate' => $cartProduct->salesRate,
                    'SaleDetails_Tax' => $cartProduct->vat,
                    'SaleDetails_TotalAmount' => $cartProduct->total,
                    'Status' => 'a',
                    'AddBy' => $this->session->userdata("FullName"),
                    'AddTime' => date('Y-m-d H:i:s'),
                    'SaleDetails_BranchId' => $this->session->userdata("BRANCHid")
                );
    
                $this->db->insert('tbl_saledetails', $saleDetails);
    
                // $this->db->query("
                //     update tbl_currentinventory 
                //     set sales_quantity = sales_quantity + ? 
                //     where product_id = ?
                //     and branch_id = ?
                // ", [$cartProduct->quantity, $cartProduct->productId, $this->session->userdata('BRANCHid')]);
            }
    
            $res = ['success'=>true, 'message'=>'Order Updated', 'salesId'=>$salesId];

        } catch (Exception $ex){
            $res = ['success'=>false, 'message'=>$ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function order_invoice()  {
        $access = $this->mt->userAccess();
        if(!$access){
            redirect(base_url());
        }
        $data['title'] = "Order Invoice"; 
		$data['content'] = $this->load->view('Administrator/order/order_invoice', $data, TRUE);
        $this->load->view('Administrator/index', $data);
    }

    public function orderInvoicePrint($saleId)  {
        $data['title'] = "Order Invoice";
        $data['salesId'] = $saleId;
        $data['content'] = $this->load->view('Administrator/order/orderAndreport', $data, TRUE);
        $this->load->view('Administrator/index', $data);
    }

    public function getOrders(){
        $data = json_decode($this->input->raw_input_stream);
        $branchId = $this->session->userdata("BRANCHid");

        $clauses = "";
        if(isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != ''){
            $clauses .= " and sm.SaleMaster_SaleDate between '$data->dateFrom' and '$data->dateTo'";
        }

        if(isset($data->userFullName) && $data->userFullName != ''){
            $clauses .= " and sm.AddBy = '$data->userFullName'";
        }

        if(isset($data->customerId) && $data->customerId != ''){
            $clauses .= " and sm.SalseCustomer_IDNo = '$data->customerId'";
        }

        if(isset($data->employeeId) && $data->employeeId != ''){
            $clauses .= " and sm.employee_id = '$data->employeeId'";
        }

        if(isset($data->customerType) && $data->customerType != ''){
            $clauses .= " and c.Customer_Type = '$data->customerType'";
        }

        if(isset($data->salesId) && $data->salesId != 0 && $data->salesId != ''){
            $clauses .= " and SaleMaster_SlNo = '$data->salesId'";
            $saleDetails = $this->db->query("
                select 
                    sd.*,
                    p.Product_Code,
                    p.Product_Name,
                    pc.ProductCategory_Name,
                    u.Unit_Name
                from tbl_saledetails sd
                join tbl_product p on p.Product_SlNo = sd.Product_IDNo
                join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
                join tbl_unit u on u.Unit_SlNo = p.Unit_ID
                where sd.SaleMaster_IDNo = ?
                and sd.Status = 'p'
            ", $data->salesId)->result();
    
            $res['saleDetails'] = $saleDetails;
        }

        $sales = $this->db->query("
            select 
                concat(sm.SaleMaster_InvoiceNo, ' - ', c.Customer_Name) as invoice_text,
                sm.*,
                c.Customer_Code,
                c.Customer_Name,
                c.Customer_Mobile,
                c.Customer_Address,
                c.Customer_Type,
                e.Employee_Name,
                br.Brunch_name
            from tbl_salesmaster sm
            left join tbl_customer c on c.Customer_SlNo = sm.SalseCustomer_IDNo
            left join tbl_employee e on e.Employee_SlNo = sm.employee_id
            left join tbl_brunch br on br.brunch_id = sm.SaleMaster_branchid
            where sm.SaleMaster_branchid = '$branchId'
            and sm.Status = 'p'
            and sm.is_order = 'true'
            $clauses
            order by sm.SaleMaster_SlNo desc
        ")->result();
        
        $res['sales'] = $sales;

        echo json_encode($res);
    }
    
    
    function order_record()  {
        $access = $this->mt->userAccess();
        if(!$access){
            redirect(base_url());
        }
        $data['title'] = "Order Record";  
        $data['content'] = $this->load->view('Administrator/order/order_record', $data, TRUE);
        $this->load->view('Administrator/index', $data); 
    }

    public function getOrderRecord(){
        $data = json_decode($this->input->raw_input_stream);
        $branchId = $this->session->userdata("BRANCHid");
        $clauses = "";
        if(isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != ''){
            $clauses .= " and sm.SaleMaster_SaleDate between '$data->dateFrom' and '$data->dateTo'";
        }

        if(isset($data->userFullName) && $data->userFullName != ''){
            $clauses .= " and sm.AddBy = '$data->userFullName'";
        }

        if(isset($data->customerId) && $data->customerId != ''){
            $clauses .= " and sm.SalseCustomer_IDNo = '$data->customerId'";
        }

        if(isset($data->employeeId) && $data->employeeId != ''){
            $clauses .= " and sm.employee_id = '$data->employeeId'";
        }

        $sales = $this->db->query("
            select 
                sm.*,
                c.Customer_Code,
                c.Customer_Name,
                c.Customer_Mobile,
                c.Customer_Address,
                e.Employee_Name,
                br.Brunch_name,
                (
                    select ifnull(count(*), 0) from tbl_saledetails sd 
                    where sd.SaleMaster_IDNo = 1
                    and sd.Status != 'd'
                ) as total_products
            from tbl_salesmaster sm
            left join tbl_customer c on c.Customer_SlNo = sm.SalseCustomer_IDNo
            left join tbl_employee e on e.Employee_SlNo = sm.employee_id
            left join tbl_brunch br on br.brunch_id = sm.SaleMaster_branchid
            where sm.SaleMaster_branchid = '$branchId'
            and sm.Status = 'p'
            and sm.is_order = 'true'
            $clauses
            order by sm.SaleMaster_SlNo desc
        ")->result();

        foreach($sales as $sale){
            $sale->saleDetails = $this->db->query("
                select 
                    sd.*,
                    p.Product_Name,
                    pc.ProductCategory_Name
                from tbl_saledetails sd
                join tbl_product p on p.Product_SlNo = sd.Product_IDNo
                join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
                where sd.SaleMaster_IDNo = ?
                and sd.status = 'p'
            ", $sale->SaleMaster_SlNo)->result();
        }

        echo json_encode($sales);
    }

    public function getOrderDetails(){
        $data = json_decode($this->input->raw_input_stream);

        $clauses = "";
        if(isset($data->customerId) && $data->customerId != ''){
            $clauses .= " and c.Customer_SlNo = '$data->customerId'";
        }

        if(isset($data->productId) && $data->productId != ''){
            $clauses .= " and p.Product_SlNo = '$data->productId'";
        }

        if(isset($data->categoryId) && $data->categoryId != ''){
            $clauses .= " and pc.ProductCategory_SlNo = '$data->categoryId'";
        }

        if(isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != ''){
            $clauses .= " and sm.SaleMaster_SaleDate between '$data->dateFrom' and '$data->dateTo'";
        }

        $saleDetails = $this->db->query("
            select 
                sd.*,
                p.Product_Code,
                p.Product_Name,
                p.ProductCategory_ID,
                pc.ProductCategory_Name,
                sm.SaleMaster_InvoiceNo,
                sm.SaleMaster_SaleDate,
                c.Customer_Code,
                c.Customer_Name
            from tbl_saledetails sd
            join tbl_product p on p.Product_SlNo = sd.Product_IDNo
            join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
            join tbl_salesmaster sm on sm.SaleMaster_SlNo = sd.SaleMaster_IDNo
            join tbl_customer c on c.Customer_SlNo = sm.SalseCustomer_IDNo
            where sd.Status = 'p'
            and sm.SaleMaster_branchid = ?
            $clauses
        ", $this->sbrunch)->result();

        echo json_encode($saleDetails);
    }

    public function  deleteOrder(){
        $res = ['success'=>false, 'message'=>''];
        try{
            $data = json_decode($this->input->raw_input_stream);
            $saleId = $data->saleId;

            $sale = $this->db->select('*')->where('SaleMaster_SlNo', $saleId)->get('tbl_salesmaster')->row();
            if($sale->Status != 'p'){
                $res = ['success'=>false, 'message'=>'Order not found'];
                echo json_encode($res);
                exit;
            }

            /*Get Sale Details Data*/
            $saleDetails = $this->db->select('Product_IDNo, SaleDetails_TotalQuantity')->where('SaleMaster_IDNo', $saleId)->get('tbl_saledetails')->result();

            foreach ($saleDetails as $detail){
                
            }

            /*Delete order Details*/
            $this->db->set('Status', 'd')->where('SaleMaster_IDNo', $saleId)->update('tbl_saledetails');

            /*Delete Sale Master Data*/
            $this->db->set('Status', 'd')->where('SaleMaster_SlNo', $saleId)->update('tbl_salesmaster');
            $res = ['success'=>true, 'message'=>'Order deleted'];
        } catch (Exception $ex){
            $res = ['success'=>false, 'message'=>$ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function  deleveredOrder(){
        $res = ['success'=>false, 'message'=>''];
        try{
            $data = json_decode($this->input->raw_input_stream);
            $saleId = $data->saleId;

            $sale = $this->db->select('*')->where('SaleMaster_SlNo', $saleId)->get('tbl_salesmaster')->row();
            if($sale->Status != 'p'){
                $res = ['success'=>false, 'message'=>'Order not found'];
                echo json_encode($res);
                exit;
            }
            

            /*Get Sale Details Data*/
            $saleDetails = $this->db->select('Product_IDNo, SaleDetails_TotalQuantity')->where('SaleMaster_IDNo', $saleId)->get('tbl_saledetails')->result();

            foreach($saleDetails as $product) {
                $stock = $this->mt->productStock($product->Product_IDNo);
                if($product->SaleDetails_TotalQuantity > $stock) {
                    $res = ['success'=>false, 'message'=>'Stock Unavailable'];
                    echo json_encode($res);
                    exit;
                }
            }

            /*deliver order Details*/
            $this->db->set('Status', 'a')->where('SaleMaster_IDNo', $saleId)->update('tbl_saledetails');

            /*deliver Sale Master Data*/
            $this->db->set('Status', 'a')->where('SaleMaster_SlNo', $saleId)->update('tbl_salesmaster');

            foreach ($saleDetails as $detail){

                /*Update Sales Inventory*/
                $this->db->query("
                    update tbl_currentinventory 
                    set sales_quantity = sales_quantity + ? 
                    where product_id = ?
                    and branch_id = ?
                ", [$detail->SaleDetails_TotalQuantity, $detail->Product_IDNo, $this->session->userdata('BRANCHid')]);

            }

            $res = ['success'=>true, 'message'=>'Order Delivery success'];
        } catch (Exception $ex){
            $res = ['success'=>false, 'message'=>$ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function deliveryOrder()
    {
        $access = $this->mt->userAccess();
        if(!$access){
            redirect(base_url());
        }
        $data['title'] = "Delivery Order Record";  
        $data['content'] = $this->load->view('Administrator/order/order_delivery_record', $data, TRUE);
        $this->load->view('Administrator/index', $data); 
    }

    public function getDeliveryOrder()
    {
        $query = $this->db->query("
            select 
                sm.*,
                c.Customer_Code,
                c.Customer_Name,
                c.Customer_Mobile,
                c.Customer_Address,
                c.Customer_Type,
                e.Employee_Name,
                br.Brunch_name
            from tbl_salesmaster sm
            left join tbl_customer c on c.Customer_SlNo = sm.SalseCustomer_IDNo
            left join tbl_employee e on e.Employee_SlNo = sm.employee_id
            left join tbl_brunch br on br.brunch_id = sm.SaleMaster_branchid
            where sm.SaleMaster_branchid = ?
            and sm.Status = 'a'
            and sm.is_order = 'true'
            order by sm.SaleMaster_SlNo desc
        ", $this->session->userdata('BRANCHid'))->result();

        echo json_encode($query);
    }


}
