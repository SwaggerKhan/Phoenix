<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use JSend;
use Illuminate\Validation\Rule;

use App\Http\Resources\User as UserResource;

class UserController extends Controller
{
    private $validation_messages = [
        'city_id.exists'    => "Can't find any city with that ID",
        'mad_email.regex'   => "The 'mad_email' you gave was not a makeadiff.in email. Enter this only if you are a fellow - and have a makeadiff.in email id.",
        'email.unique'      => 'Entered Email ID already exists in the MAD database',
        'phone.unique'      => 'Entered Phone already exists in the MAD database',
        'sex.regex'         => "Sex field should have one of these values - 'm','f' or 'o'"
    ];

    public function add(Request $request)
    {
        $validation_rules = [
            'name'      => 'required|max:50',
            'email'     => 'required|email|unique:User,email,well_wisher,user_type,user_type,!alumni',
            'mad_email' => 'email|regex:/.+\@makeadiff\.in$/',
            'password'  => 'required',
            'sex'       => 'regex:/^[mfo]$/',
            'phone'     => 'required|unique:User,phone,well_wisher,user_type,user_type,!alumni|regex:/[\+0-9]{10}/',
            'city_id'   => 'required|numeric|exists:City,id'
        ];

        $validator = \Validator::make($request->all(), $validation_rules, $this->validation_messages);

        if ($validator->fails()) {
            return JSend::fail("Unable to create user - errors in input", $validator->errors(), 400);
        }

        $user = new User;
        $result = $user->add($request->all());

        return JSend::success("Created the user successfully", array('users' => $result));
    }

    public function edit(Request $request, $user_id)
    {
        $user = new User;
        $exists = $user->fetch($user_id, false);

        if (!$exists) {
            return JSend::fail("Can't find any user with the given ID", [], 404);
        }

        $validation_rules = [
            'name'      => 'max:50',
            'email'     => ['email', Rule::unique('User')->ignore($user_id)],
            'mad_email' => ['email|nullable|regex:/.+\@makeadiff\.in$/', Rule::unique('User')->ignore($user_id)],
            'sex'       => 'regex:/^[mfo]$/',
            'phone'     => ['regex:/[\+0-9]{10,13}/', Rule::unique('User')->ignore($user_id)],
            'city_id'   => 'numeric|exists:City,id'
        ];

        $validator = \Validator::make($request->all(), $validation_rules, $this->validation_messages);

        if ($validator->fails()) {
            return JSend::fail("Unable to create user - errors in input.", $validator->errors(), 400);
        }

        $result = $user->find($user_id)->edit($request->all());

        return JSend::success("Edited the user", array('users' => $result));
    }

    public function index(Request $request){    

        $search_fields = ['id','user_id', 'identifier', 'name','phone','email','mad_email','any_email','group_id','group_in','vertical_id','city_id',
                            'user_type','center_id','project_id', 'not_user_type', 'credit', 'credit_lesser_than', 'credit_greater_than'];
        $search = [];
        foreach ($search_fields as $key) {
            if (!$request->has($key)) {
                continue;
            }

            if ($key == 'group_id') {
                $search['user_group'] = [$request->input('group_id')];
            } elseif ($key == 'group_in') {
                $search['user_group'] = explode(",", $request->input('group_in'));
            } elseif ($key == 'not_user_type') {
                $search['not_user_type'] = explode(",", $request->input('not_user_type'));
            } else {
                $search[$key] = $request->input($key);
            }
        }
        if (!isset($search['project_id'])) {
            $search['project_id'] = 1;
        }
        
        

        $user = new User;
        $data = $user->search($search, true);
        return JSend::success("Users", array('users' => $data));;
        // return UserResource::collection($data);
    }
}
