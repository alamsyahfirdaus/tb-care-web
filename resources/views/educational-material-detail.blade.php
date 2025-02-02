@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header py-2">
                <h3 class="card-title pt-1">{{ $title }}</h3>
                <div class="card-tools">
                    <a href="{{ route('materials') }}" class="btn btn-primary btn-sm" title="Sebelumnya">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="callout" style="border-left-color: #007bff;">
                    <h5>{{ $data['title_material'] }}</h5>
                    @if ($data['description'])
                        <p>{{ $data['description'] }}</p>
                    @endif
                </div>
                @if ($data['material_url'])
                    @php
                        $url = $data['material_url'];
                        if (strpos($url, 'youtube.com/watch') !== false) {
                            $url = str_replace('youtube.com/watch?v=', 'youtube.com/embed/', $url);
                        }
                    @endphp
                    {{-- @if ($data['thumbnail'])
                        <div style="text-align: center; margin-bottom: 10px;">
                            <img src="{{ asset('storage/materials/' . $data['thumbnail']) }}" alt="Thumbnail"
                                style="width: 100%; max-width: 400px; border-radius: 8px;">
                        </div>
                    @endif --}}
                    <div class="embed-responsive embed-responsive-16by9">
                        <iframe class="embed-responsive-item" src="{{ $url }}" allowfullscreen></iframe>
                    </div>
                @elseif($data['material_file'])
                    @if (strpos($data['material_file'], '.pdf') !== false)
                        <div class="material_file" style="width: 100%;">
                            <object data="{{ asset('storage/materials/' . $data['material_file']) }}" type="application/pdf"
                                style="width: 100%; height: 600px;">
                                <a href="{{ asset('storage/materials/' . $data['material_file']) }}"
                                    class="btn btn-block btn-primary">
                                    <i class="fas fa-download mr-1"></i> Unduh Materi
                                </a>
                            </object>
                        </div>
                    @elseif (in_array(pathinfo($data['material_file'], PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png']))
                        <div class="material_file" style="text-align: center; width: 100%;">
                            <img src="{{ asset('storage/materials/' . $data['material_file']) }}" alt="Material Image"
                                style="width: 50%;">
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
    <style>
        .embed-responsive,
        .material_file {
            border-radius: 0.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
            background-color: #fff;
            margin-bottom: 1rem;
            padding: 1rem;
        }
    </style>
@endsection
