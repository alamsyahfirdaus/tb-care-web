@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            @if (Auth::id() != $user->id)
                <div class="card-header py-2">
                    <h3 class="card-title pt-1">{{ 'Data ' . $title }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('user.list', ['id' => base64_encode($user_type_id)]) }}"
                            class="btn btn-primary btn-sm" title="Sebelumnya">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </div>

                </div>
            @endif
            <div class="row">
                <div class="col-md-3">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <img class="profile-user-img img-fluid img-circle"
                                src="{{ $user->profile ? asset('upload_images/' . $user->profile) : asset('assets/img/profile.png') }}"
                                alt="{{ $user->username }}">
                        </div>
                        <h3 class="profile-username text-center">
                            {{ $user->name }}</h3>
                        <p class="text-muted text-center">
                            {{ $user->userType->name }}</p>
                        <ul class="list-group list-group-unbordered">
                            <li class="list-group-item" style="border-bottom: none;">
                                @php
                                    $route =
                                        $user->id == Auth::id()
                                            ? route('profile.edit', ['id' => base64_encode($user->id)])
                                            : route('user.edit', ['id' => base64_encode($user->id)]);
                                    $buttonText = $user->id == Auth::id() ? 'Edit Profil' : 'Edit Data ' . $title;
                                @endphp

                                <a href="{{ $route }}" type="button"
                                    class="btn btn-outline-primary btn-block btn-sm">
                                    <i class="fas fa-user-edit"></i> {{ $buttonText }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="card-body">
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" style="background-color: #fff;"><i
                                            class="fas fa-filter"></i></span>
                                </div>
                                <select name="user_id" id="user_id" class="form-control select2"
                                    {{ $user_type_id == 1 ? 'disabled="disabled"' : '' }}>
                                    @foreach ($users as $item)
                                        <option value="{{ base64_encode($item->id) }}"
                                            {{ $user->id == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }} ({{ $item->username }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <ul class="list-group list-group-unbordered">
                            @php
                                $listData = [
                                    'Nama' => $user->name ?? '-',
                                    'Email' => $user->email ?? '-',
                                    'Telepon/HP' => $user->telephone,
                                    'Jenis Kelamin' => $user->gender ?? '-',
                                    'Tempat, Tanggal Lahir' =>
                                        $user->place_of_birth && $user->date_of_birth
                                            ? $user->place_of_birth .
                                                ', ' .
                                                \App\Helpers\DateHelper::convertDate($user->date_of_birth)
                                            : '-',
                                ];
                            @endphp
                            @foreach ($listData as $key => $value)
                                <li class="list-group-item">
                                    <span>{{ $key }}</span> <span class="float-right">{{ $value }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        @if (($user_type_id != 1 && $user_type_id == 4 && $user->id != Auth::id()) || $user_type_id == 2 || $user_type_id == 3)
            <div class="card card-primary card-outline">
                <div class="card-header py-2">
                    <h3 class="card-title pt-1 card-title-patient">Detail {{ $title }}</h3>
                    <div class="card-tools">
                        @php
                            $routes = [
                                2 => 'ho.edit',
                                3 => 'coord.edit',
                                4 => 'patient.edit',
                            ];
                        @endphp
                        @if (isset($routes[$user_type_id]))
                            <a href="{{ route($routes[$user_type_id], ['id' => base64_encode($user_detail_id)]) }}"
                                class="btn btn-primary btn-sm" title="Edit Detail {{ $title }}">
                                <i class="fas fa-edit"></i>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-unbordered">
                        @foreach ($user_detail as $key => $value)
                            <li class="list-group-item"
                                @if ($loop->first) style="border-top: 0px; padding-top: 0px;" @endif>
                                <span>{{ $key }}</span>
                                <span class="float-right">{{ $value }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>

    <script>
        $('#user_id').change(function() {
            var userId = $(this).val();
            if (userId) {
                var url = '{{ route('user.show', ['id' => ':id']) }}';
                window.location.href = url.replace(':id', userId);
            }
        });
    </script>
@endsection
