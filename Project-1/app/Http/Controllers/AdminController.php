<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Super Admin', ['only'=> ['approveUser','getAllAdmin']]);
        //$this->middleware('', [''=> ['','']]);

    }
    public function addAdmin(Request $request){

        $registerAdminData = $request->validate([
            'name'=>'required|string',
            'email'=>'required|string|email|unique:users',
            'password'=>'required|min:8|confirmed',
            'role_id'=>'required|numeric',
            'image'=> 'image|mimes:jpg,png,jpeg,gif,svg|max:2048',
        ]);
        try{
            $role=Role::where('id',$request->role_id)->first();
        }catch(\Exception $exception){
            return response()->json([
                'message'=>$exception->getMessage()
            ],404);
        }
        $admin =new User;

        if($request->hasFile('image')){
        /*
         $image = $request->file('image');
         $image_name=time() . '.' . $image->getClientOriginalExtension();
         Storage::putFileAs('ProfileImage/',$image,$image_name);
         $admin->query()->update([
            'image'=>"ProfileImage/$image_name"
          ]);
        */
            $image = $request->file('image');
            $image_name=time() . '.' . $image->getClientOriginalExtension();
            $image->move('ProfileImage/',$image_name);
       /*
         Storage::putFileAs('ProfileImage/',$image,$image_name);
           $user->query()->update([
                'image'=>"ProfileImage/$image_name"
            ]);
        */
            $admin->image="ProfileImage/".$image_name;
        }

        $admin->name=$registerAdminData['name'];
        $admin->email=$registerAdminData['email'];
        $admin->password= Hash::make($registerAdminData['password']);
        $admin->assignRole($role->name);
        // if($request->has('role')){
        //     $admin->assignRole($request->role);
        // }

        $admin->save();

        $token = $admin->createToken('token')->plainTextToken;
        $data=[
            'id'=> $admin->id,
            'name'=> $admin->name,
            'email'=> $admin->email,
            'image'=> $admin->image,
            'position'=>$admin->position,
            'is_approved'=>$admin->is_approved,
            //'token'=> $token,
        ];
        return response()->json([
             //'data'=>$admin,
             //'token'=>$token
            'data'=>$data,
        ],200);
    }

    public function login(Request $request)
    {
        $loginAdminData = $request->validate([
            'email'=>'required|string|email',
            'password'=>'required|min:8'
        ]);

        $admin = User::where('email',$loginAdminData['email'])->first();

        if(!$admin || !Hash::check($loginAdminData['password'],$admin->password)){
            return response()->json([
                'message' => 'Invalid Credentials'
            ],401);
        }
        $token = $admin->createToken('token')->plainTextToken;
        return response()->json([
            'message'=> 'login done',
            'role'=>$admin->roles()->pluck('name'),
            'token' => $token,
        ],200);

    }

    public function logout(Request $request)
    {

         $request->user()->tokens()->delete();
        return response()->json([
            'message' => 'Successfully logged out'
        ],200);
    }

    public function profile()
    {

        $admin=auth()->user();


        $data=[
            'id'=>$admin->id,
            'name'=>$admin->name,
            'email'=> $admin->email,
            'image'=> $admin->image,
            'position'=>$admin->position,
            'role'=> $admin->roles->pluck('name'),
        ];

        return response()->json([
            'data'=>$data,
        ],200);
    }

    public function changeProfilePhoto(Request $request)
    {
        $data=$request->validate([
            'image'=> 'required|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
        ]);
        $admin=User::find(auth()->user()->id);
        if(File::exists($admin->image))
        {
            File::delete($admin->image);
        }
        $image = $request->file('image');
        $image_name=time() . '.' . $image->getClientOriginalExtension();
        $image->move('ProfileImage/',$image_name);
        $admin->image="ProfileImage/".$image_name;
        $admin->save();
        $data=[
            'id'=>$admin->id,
            'name'=>$admin->name,
            'email'=> $admin->email,
            'image'=> $admin->image,
        ];
        return response()->json([
            'message'=>'photo updated successfully',
            'data'=>$data
        ],200);

    }

    public function deleteProfilePhoto()
    {

        $admin=User::find(auth()->user()->id);

        if($admin->image==null){
            return response()->json([
                'message'=> 'u do not has a profile image to delete'
            ],200);
        }

        if(File::exists($admin->image))
        {
            File::delete($admin->image);
        }
        $admin->image=null;
        $admin->save();

        $data=[
            'id'=>$admin->id,
            'name'=>$admin->name,
            'email'=> $admin->email,
            'image'=> $admin->image,
        ];
        return response()->json([
            'message'=>'photo updated successfully',
            //'data'=>$user->get(['id','name','email','image'])
            'data'=>$data,
        ],200);

    }


    public function changeName(Request $request)
    {

        $data=$request->validate([
            'name'=> 'required|string'
        ]);
        $user=User::find(auth()->user()->id);
        $user->name=$data['name'];
        $user->save();

        return response()->json([
            'message'=> 'name updated successfully'
        ],200);

    }

    public function getAllAdmin()
    {

        $user=User::Role('Admin')->get(['id','name','email','image']);
        return response()->json([
            'data'=> $user,
        ],200);

    }

    public function getAdmin($id)
    {

        $user=User::where('id',$id)->get(['id','name','email','image']);
        return response()->json([
            'data'=> $user,
        ],200);

    }

    public function getAdmisForRole($id)
    {

       $role=Role::where('id',$id)->first();
       $admins=User::Role($role->name)->get(['id','name','email']);

       return response()->json([
        'data'=> $admins,
       ],200);

    }

    public function approveUser(Request $request)
    {
        $validatedData = Validator::make($request->all(),[
            'user_id' => 'required|numeric|exists:users,id'
        ]);
        if( $validatedData->fails() ){
            return response()->json([
                'message'=> $validatedData->errors()->first(),
            ],422);
        }
        $user=User::where('id',$request->user_id)->first();
        $user->is_approved = true;
        $user->save();

        // Notification::send($user, new UserApprovedNotification());

        return response()->json([
            'message'=>'Admin accepted'
        ],200);
    }

}
