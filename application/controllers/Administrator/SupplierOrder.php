<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class SupplierOrder extends CI_Controller {
    
    public function __construct()
    {
        parent::__construct();
        $this->brunch = $this->session->userdata('BRANCHid');
        $access = $this->session->userdata('userId');
        if ($access == '') {
            redirect("Login");
        }
        $this->load->model('Billing_model');
        $this->load->library('cart');
        $this->load->model('Model_table', "mt", TRUE);
        $this->load->helper('form');
    }

    public function index()
    {
        $access = $this->mt->userAccess();
        if(!$access){
            redirect(base_url());
        }
        $data['title'] = "Supplier Order";

        $invoice = $this->mt->generatePurchaseInvoice();

        $data['purchaseId'] = 0;
        $data['invoice'] = $invoice;
        $data['content'] = $this->load->view('Administrator/order/supplier_order', $data, TRUE);
        $this->load->view('Administrator/index', $data);
    }

    public function addSupplierOrder()
    {
        $res = ['success'=>false, 'message'=>''];
        try{
            $data = json_decode($this->input->raw_input_stream);

            $invoice = $data->purchase->invoice;
            $invoiceCount = $this->db->query("select * from tbl_purchasemaster where PurchaseMaster_InvoiceNo = ?", $invoice)->num_rows();
            if($invoiceCount != 0){
                $invoice = $this->mt->generatePurchaseInvoice();
            }

            $supplierId = $data->purchase->supplierId;
            if(isset($data->supplier)){
                $supplier = (array)$data->supplier;
                unset($supplier['Supplier_SlNo']);
                unset($supplier['display_name']);
                $supplier['Supplier_Code'] = $this->mt->generateSupplierCode();
                $supplier['Status'] = 'a';
                $supplier['AddBy'] = $this->session->userdata("FullName");
                $supplier['AddTime'] = date('Y-m-d H:i:s');
                $supplier['Supplier_brinchid'] = $this->session->userdata('BRANCHid');

                $this->db->insert('tbl_supplier', $supplier);
                $supplierId = $this->db->insert_id();
            }

            $purchase = array(
                'Supplier_SlNo' => $supplierId,
                'PurchaseMaster_InvoiceNo' => $invoice,
                'PurchaseMaster_OrderDate' => $data->purchase->purchaseDate,
                'PurchaseMaster_PurchaseFor' => $data->purchase->purchaseFor,
                'PurchaseMaster_TotalAmount' => $data->purchase->total,
                'PurchaseMaster_DiscountAmount' => $data->purchase->discount,
                'PurchaseMaster_Tax' => $data->purchase->vat,
                'PurchaseMaster_Freight' => $data->purchase->freight,
                'PurchaseMaster_AdjustAmount' => $data->purchase->adjustAmount,
                'PurchaseMaster_SubTotalAmount' => $data->purchase->subTotal,
                'PurchaseMaster_PaidAmount' => $data->purchase->paid,
                'PurchaseMaster_DueAmount' => $data->purchase->due,
                'previous_due' => $data->purchase->previousDue,
                'PurchaseMaster_Description' => $data->purchase->note,
                'status' => 'p',
                'is_order' => 'true',
                'AddBy' => $this->session->userdata("FullName"),
                'AddTime' => date('Y-m-d H:i:s'),
                'PurchaseMaster_BranchID' => $this->session->userdata('BRANCHid')
            );

            $this->db->insert('tbl_purchasemaster', $purchase);
            $purchaseId = $this->db->insert_id();

            foreach($data->cartProducts as $product){
                $purchaseDetails = array(
                    'PurchaseMaster_IDNo' => $purchaseId,
                    'Product_IDNo' => $product->productId,
                    'PurchaseDetails_TotalQuantity' => $product->quantity,
                    'PurchaseDetails_Rate' => $product->purchaseRate,
                    'PurchaseDetails_TotalAmount' => $product->total,
                    'Status' => 'p',
                    'AddBy' => $this->session->userdata("FullName"),
                    'AddTime' => date('Y-m-d H:i:s'),
                    'PurchaseDetails_branchID' => $this->session->userdata('BRANCHid')
                );

                $this->db->insert('tbl_purchasedetails', $purchaseDetails);
               
            }

            $res=['success'=>true, 'message'=>'Supplier Order Success', 'purchaseId'=>$purchaseId];
        } catch (Exception $ex){
            $res = ['success'=>false, 'message'=>$ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function supplierOrderEdit($purchaseId){
        $data['title'] = "Supplier Order";
        $data['purchaseId'] = $purchaseId;
        $data['invoice'] = $this->db->query("select PurchaseMaster_InvoiceNo from tbl_purchasemaster where PurchaseMaster_SlNo = ?", $purchaseId)->row()->PurchaseMaster_InvoiceNo;
        $data['content'] = $this->load->view('Administrator/order/supplier_order', $data, TRUE);
        $this->load->view('Administrator/index', $data);
    }

    public function updateSupplierOrder()
    {
        $res = ['success'=>false, 'message'=>''];
        try{
            $data = json_decode($this->input->raw_input_stream);
            $purchaseId = $data->purchase->purchaseId;

            if(isset($data->supplier)){
                $supplier = (array)$data->supplier;
                unset($supplier['Supplier_SlNo']);
                unset($supplier['display_name']);
                $supplier['UpdateBy'] = $this->session->userdata("FullName");
                $supplier['UpdateTime'] = date('Y-m-d H:i:s');

                $this->db->where('Supplier_SlNo', $data->purchase->supplierId)->update('tbl_supplier', $supplier);
            }

            $purchase = array(
                'Supplier_SlNo' => $data->purchase->supplierId,
                'PurchaseMaster_InvoiceNo' => $data->purchase->invoice,
                'PurchaseMaster_OrderDate' => $data->purchase->purchaseDate,
                'PurchaseMaster_PurchaseFor' => $data->purchase->purchaseFor,
                'PurchaseMaster_TotalAmount' => $data->purchase->total,
                'PurchaseMaster_DiscountAmount' => $data->purchase->discount,
                'PurchaseMaster_Tax' => $data->purchase->vat,
                'PurchaseMaster_Freight' => $data->purchase->freight,
                'PurchaseMaster_AdjustAmount' => $data->purchase->adjustAmount,
                'PurchaseMaster_SubTotalAmount' => $data->purchase->subTotal,
                'PurchaseMaster_PaidAmount' => $data->purchase->paid,
                'PurchaseMaster_DueAmount' => $data->purchase->due,
                'previous_due' => $data->purchase->previousDue,
                'PurchaseMaster_Description' => $data->purchase->note,
                'status' => 'p',
                'is_order' => 'true',
                'UpdateBy' => $this->session->userdata("FullName"),
                'UpdateTime' => date('Y-m-d H:i:s'),
                'PurchaseMaster_BranchID' => $this->session->userdata('BRANCHid')
            );

            $this->db->where('PurchaseMaster_SlNo', $purchaseId);
            $this->db->update('tbl_purchasemaster', $purchase);

            $this->db->query("delete from tbl_purchasedetails where PurchaseMaster_IDNo = ?", $purchaseId);

            foreach($data->cartProducts as $product){
                $purchaseDetails = array(
                    'PurchaseMaster_IDNo' => $purchaseId,
                    'Product_IDNo' => $product->productId,
                    'PurchaseDetails_TotalQuantity' => $product->quantity,
                    'PurchaseDetails_Rate' => $product->purchaseRate,
                    'PurchaseDetails_TotalAmount' => $product->total,
                    'Status' => 'p',
                    'UpdateBy' => $this->session->userdata("FullName"),
                    'UpdateTime' => date('Y-m-d H:i:s'),
                    'PurchaseDetails_branchID' => $this->session->userdata('BRANCHid')
                );

                $this->db->insert('tbl_purchasedetails', $purchaseDetails);
                
            }
            
            $res=['success'=>true, 'message'=>'Purchase Success', 'purchaseId'=>$purchaseId];
        } catch (Exception $ex){
            $res = ['success'=>false, 'message'=>$ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function order_bill()
    {
        $access = $this->mt->userAccess();
        if(!$access){
            redirect(base_url());
        }
        $data['title'] = "Order Invoice";
        $data['content'] = $this->load->view('Administrator/purchase/purchase_bill', $data, TRUE);
        $this->load->view('Administrator/index', $data);
    }


    public function supplierInvoicePrint($purchaseId)
    {
        $data['title'] = "Supplier Order Invoice";
        $data['purchaseId'] = $purchaseId;
        $data['content'] = $this->load->view('Administrator/order/supplierOrderReport', $data, TRUE);
        $this->load->view('Administrator/index', $data);
    }

    public function supplierOrderRecord()
    {
        $access = $this->mt->userAccess();
        if(!$access){
            redirect(base_url());
        }
        $data['title'] = "Supplier Order Record";
        $data['content'] = $this->load->view('Administrator/order/supplier_order_record', $data, TRUE);
        $this->load->view('Administrator/index', $data);
    }

    public function getSupplierOrder(){
        $data = json_decode($this->input->raw_input_stream);
        $branchId = $this->session->userdata('BRANCHid');

        $clauses = "";
        if(isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != ''){
            $clauses .= " and pm.PurchaseMaster_OrderDate between '$data->dateFrom' and '$data->dateTo'";
        }
        
        if(isset($data->supplierId) && $data->supplierId != ''){
            $clauses .= " and pm.Supplier_SlNo = '$data->supplierId'";
        }

        $purchaseIdClause = "";
        if(isset($data->purchaseId) && $data->purchaseId != null){
            $purchaseIdClause = " and pm.PurchaseMaster_SlNo = '$data->purchaseId'";

            $res['purchaseDetails'] = $this->db->query("
                select
                    pd.*,
                    p.Product_Name,
                    p.Product_Code,
                    p.ProductCategory_ID,
                    p.Product_SellingPrice,
                    pc.ProductCategory_Name,
                    u.Unit_Name
                from tbl_purchasedetails pd 
                join tbl_product p on p.Product_SlNo = pd.Product_IDNo
                join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
                join tbl_unit u on u.Unit_SlNo = p.Unit_ID
                where pd.PurchaseMaster_IDNo = '$data->purchaseId'
                and pd.Status = 'p'
            ")->result();
        }
        $purchases = $this->db->query("
            select
                concat(pm.PurchaseMaster_InvoiceNo, ' - ', s.Supplier_Name) as invoice_text,
                pm.*,
                s.Supplier_Name,
                s.Supplier_Mobile,
                s.Supplier_Email,
                s.Supplier_Code,
                s.Supplier_Address,
                s.Supplier_Type
            from tbl_purchasemaster pm
            join tbl_supplier s on s.Supplier_SlNo = pm.Supplier_SlNo
            where pm.PurchaseMaster_BranchID = '$branchId' 
            and pm.status = 'p'
            and pm.is_order = 'true'
            $purchaseIdClause $clauses
            order by pm.PurchaseMaster_SlNo desc
        ")->result();

        $res['purchases'] = $purchases;
        echo json_encode($res);
    }

    public function getSupplierOrderRecord(){
        $data = json_decode($this->input->raw_input_stream);
        $branchId = $this->session->userdata("BRANCHid");
        $clauses = "";
        if(isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != ''){
            $clauses .= " and pm.PurchaseMaster_OrderDate between '$data->dateFrom' and '$data->dateTo'";
        }

        if(isset($data->userFullName) && $data->userFullName != ''){
            $clauses .= " and pm.AddBy = '$data->userFullName'";
        }

        if(isset($data->supplierId) && $data->supplierId != ''){
            $clauses .= " and pm.Supplier_SlNo = '$data->supplierId'";
        }

        $purchases = $this->db->query("
            select 
                pm.*,
                s.Supplier_Code,
                s.Supplier_Name,
                s.Supplier_Mobile,
                s.Supplier_Address,
                br.Brunch_name
            from tbl_purchasemaster pm
            left join tbl_supplier s on s.Supplier_SlNo = pm.Supplier_SlNo
            left join tbl_brunch br on br.brunch_id = pm.PurchaseMaster_BranchID
            where pm.PurchaseMaster_BranchID = '$branchId'
            and pm.status = 'p'
            and pm.is_order = 'true'
            $clauses
        ")->result();

        foreach($purchases as $purchase){
            $purchase->purchaseDetails = $this->db->query("
                select 
                    pd.*,
                    p.Product_Name,
                    pc.ProductCategory_Name
                from tbl_purchasedetails pd
                join tbl_product p on p.Product_SlNo = pd.Product_IDNo
                join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
                where pd.PurchaseMaster_IDNo = ?
                and pd.Status = 'p'
            ", $purchase->PurchaseMaster_SlNo)->result();
        }

        echo json_encode($purchases);
    }

    public function getSupplierOrderDetails(){
        $data = json_decode($this->input->raw_input_stream);

        $clauses = "";
        if(isset($data->supplierId) && $data->supplierId != ''){
            $clauses .= " and s.Supplier_SlNo = '$data->supplierId'";
        }

        if(isset($data->productId) && $data->productId != ''){
            $clauses .= " and p.Product_SlNo = '$data->productId'";
        }

        if(isset($data->categoryId) && $data->categoryId != ''){
            $clauses .= " and pc.ProductCategory_SlNo = '$data->categoryId'";
        }

        if(isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != ''){
            $clauses .= " and pm.PurchaseMaster_OrderDate between '$data->dateFrom' and '$data->dateTo'";
        }

        $saleDetails = $this->db->query("
            select 
                pd.*,
                p.Product_Name,
                pc.ProductCategory_Name,
                pm.PurchaseMaster_InvoiceNo,
                pm.PurchaseMaster_OrderDate,
                s.Supplier_Code,
                s.Supplier_Name
            from tbl_purchasedetails pd
            join tbl_product p on p.Product_SlNo = pd.Product_IDNo
            join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
            join tbl_purchasemaster pm on pm.PurchaseMaster_SlNo = pd.PurchaseMaster_IDNo
            join tbl_supplier s on s.Supplier_SlNo = pm.Supplier_SlNo
            where pd.Status = 'p'
            and pd.PurchaseDetails_branchID = '$this->brunch'
            $clauses
        ")->result();

        echo json_encode($saleDetails);
    }

    /*Delete Purchase Record*/
    public function  deleteSupplierOrder(){
        $res = ['success'=>false, 'message'=>''];
        try{
            $data = json_decode($this->input->raw_input_stream);
            $purchase = $this->db->select('*')->where('PurchaseMaster_SlNo', $data->purchaseId)->get('tbl_purchasemaster')->row();
            if($purchase->status != 'p'){
                $res = ['success'=>false, 'message'=>'Supplier Order not found'];
                echo json_encode($res);
                exit;
            }

            /*Delete Purchase Details*/
            $this->db->set('Status', 'd')->where('PurchaseMaster_IDNo',$data->purchaseId)->update('tbl_purchasedetails');

            /*Delete Purchase Master Data*/
            $this->db->set('status', 'd')->where('PurchaseMaster_SlNo',$data->purchaseId)->update('tbl_purchasemaster');
            
            $res = ['success'=>true, 'message'=>'Successfully deleted'];
        } catch (Exception $ex){
            $res = ['success'=>false, 'message'=>$ex->getMessage()];
        }
        
        echo json_encode($res);
    }


    public function deliveredSupplierOrder() {
        $res = ['success'=>false, 'message'=>''];
        try{
            $data = json_decode($this->input->raw_input_stream);
            $purchase = $this->db->select('*')->where('PurchaseMaster_SlNo', $data->purchaseId)->get('tbl_purchasemaster')->row();
            if($purchase->status != 'p'){
                $res = ['success'=>false, 'message'=>'Supplier Order not found'];
                echo json_encode($res);
                exit;
            }

            /*Get Purchase Details Data*/
            $purchaseDetails = $this->db->select('Product_IDNo,PurchaseDetails_TotalQuantity,PurchaseDetails_TotalAmount,PurchaseDetails_Rate')->where('PurchaseMaster_IDNo',$data->purchaseId)->get('tbl_purchasedetails')->result();


            /*Delete Purchase Details*/
            $this->db->set('Status', 'a')->where('PurchaseMaster_IDNo',$data->purchaseId)->update('tbl_purchasedetails');

            /*Delete Purchase Master Data*/
            $this->db->set('status', 'a')->where('PurchaseMaster_SlNo',$data->purchaseId)->update('tbl_purchasemaster');

            foreach($purchaseDetails as $product){
                $previousStock = $this->mt->productStock($product->Product_IDNo);

                $this->db->query("
                    update tbl_currentinventory 
                    set purchase_quantity = purchase_quantity + ? 
                    where product_id = ?
                    and branch_id = ?
                ", [$product->PurchaseDetails_TotalQuantity, $product->Product_IDNo, $this->session->userdata('BRANCHid')]);

                // $this->db->query("
                //     update tbl_product set 
                //     Product_Purchase_Rate = (((Product_Purchase_Rate * ?) + ?) / ?) 
                //     where Product_SlNo = ?
                // ", [
                //     $previousStock,
                //     $product->PurchaseDetails_TotalAmount,
                //     ($previousStock + $product->PurchaseDetails_TotalQuantity),
                //     $product->Product_IDNo
                // ]);
                $this->db->query("
                    update tbl_product set 
                    Product_Purchase_Rate =  ?
                    where Product_SlNo = ?
                ", [
                    $product->PurchaseDetails_Rate, 
                    $product->Product_IDNo
                ]);
            }
            
            $res = ['success'=>true, 'message'=>'Successfully Delivered'];
        } catch (Exception $ex){
            $res = ['success'=>false, 'message'=>$ex->getMessage()];
        }
        
        echo json_encode($res);
    }
 
}