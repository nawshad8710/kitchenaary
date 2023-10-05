<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use App\Models\AccountHead;
use App\Models\AccountLedger;
use App\Models\Order;
use App\Models\OrderDetail;
use Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;

class PosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::where('status', 1)->latest()->get();
        $categories = Category::where('status', 1)->latest()->get();
        $brands = Brand::where('status', 1)->latest()->get();
        $customers = User::where('role', 3)->where('status', 1)->latest()->get();
        return view('backend.pos.index', compact('products', 'categories', 'brands', 'customers'));
    }

    public function getProduct($id)
    {
        $product = Product::findOrFail($id);
        return json_encode($product);
    }

    public function filter()
    {
        $products = Product::where('status', 1);
        if(isset($_GET['search_term'])){
            $products = $products->where('name_en', 'like', '%'.$_GET['search_term'].'%');
        }
        if(isset($_GET['category_id'])){
            $products = $products->where('category_id', $_GET['category_id']);
        }
        if(isset($_GET['brand_id'])){
            $products = $products->where('brand_id', $_GET['brand_id']);
        }
        $products = $products->get();
        return json_encode($products);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $product_ids = $request->product_id;

        if(!$product_ids || count($product_ids)<=0){
            $notification = array(
                'message' => 'Add at least one product.', 
                'alert-type' => 'error'
            );
            return redirect()->back()->with($notification);
        }

        //dd($request);

        $user = User::findOrFail($request->customer_id);

        //dd($user);

        if($request->payment_method == NULL) {
            $request->payment_method = "cash";
        }

        if($request->payment_status == NULL) {
            $request->payment_status = 0;
        }

        if($user->phone == NULL) {
            $user->phone = "";
        }

        if($user->email == NULL) {
            $user->email = "";
        }

        $order = Order::create([
            'user_id' => $request->customer_id,
            'grand_total' => $request->total,
            'payment_method' => $request->payment_method,
            'payment_status' => $request->payment_status,
            'invoice_no' => date('Ymd-His') . rand(10, 99),
            'delivery_status' => 'pending',
            'phone' => $user->phone,
            'email' => $user->email,
            'address' => $user->address,
            'type' => 3,
            //'created_by' => Auth::guard('admin')->user()->id,
        ]);

        // order details add //
        for($i=0; $i<count($product_ids); $i++) {
            //$product = Product::find($product_ids[$i]);
            OrderDetail::insert([
                'order_id' => $order->id, 
                'product_id' => $product_ids[$i],
                'is_varient' => 0,
                'qty' => $request->qty[$i],
                'price' => $request->price[$i],
                'created_at' => Carbon::now(),
            ]);
        }

        //Ledger Entry
        $ledger = AccountLedger::create([
            'account_head_id' => 2,
            'particulars' => 'Order ID: '.$order->id,
            'credit' => $order->grand_total,
            'order_id' => $order->id,
            'type' => 2,
        ]);
        $ledger->balance = get_account_balance() + $order->grand_total;
        $ledger->save();

        $notification = array(
            'message' => 'Your order has been placed successfully.', 
            'alert-type' => 'success'
        );
        return redirect()->back()->with($notification);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
