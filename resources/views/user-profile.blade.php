@extends('layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6">
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <img class="profile-user-img" src="{{ asset('profile_images/' . $user->profile) }}"
                                alt="">
                        </div>
                        {{-- <div class="text-center">
                            <button type="button" class="btn btn-primary btn-sm text-center">Upload</button>
                        </div> --}}
                        <h3 class="profile-username text-center">{{ $user->name }}</h3>
                        <p class="text-muted text-center">{{ $user->userType->user_type }}</p>
                        {{-- <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Email</b> <span class="float-right">{{ $user->email }}</span>
                            </li>
                        </ul> --}}
                        {{-- <a href="#" class="btn btn-primary btn-block"><b>Update</b></a> --}}
                    </div>
                </div>
            </div>
        </div>
@endsection
