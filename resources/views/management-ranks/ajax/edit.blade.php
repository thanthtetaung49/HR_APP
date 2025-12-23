@extends('layouts.app')

@section('content')
    <style>
        .mt {
            margin-top: -4px;
        }
    </style>

    <div class="row p-20">
        <div class="col-sm-12">
            <x-form action="{{ route('management-ranks.update', $managementRank->id) }}" method="PUT">
                @csrf
                <div class="add-client bg-white rounded">
                    <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                        @lang('app.menu.editManagementRank')</h4>

                    <div class="row p-20">
                        <div class="col-md-6">
                            <x-forms.text fieldId="name" :fieldLabel="__('app.menu.managementRanks')" fieldName="name" fieldRequired="true"
                                :fieldPlaceholder="__('placeholders.managementRanks')" :fieldValue="old('name', $managementRank->name)">
                            </x-forms.text>

                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <x-forms.label class="mt-3" fieldId="rank" :fieldLabel="__('app.menu.rank')" fieldName="rank">
                            </x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker" name="rank[]" id="rank"
                                    data-live-search="true" multiple>
                                    <option value="">--</option>
                                    <option value="1" @if (json_decode($managementRank->rank) && in_array(1, json_decode($managementRank->rank))) selected @endif>Rank 1</option>
                                    <option value="2" @if (json_decode($managementRank->rank) && in_array(2, json_decode($managementRank->rank))) selected @endif>Rank 2</option>
                                    <option value="3" @if (json_decode($managementRank->rank) && in_array(3, json_decode($managementRank->rank))) selected @endif>Rank 3</option>
                                    <option value="4" @if (json_decode($managementRank->rank) && in_array(4, json_decode($managementRank->rank))) selected @endif>Rank 4</option>
                                    <option value="5" @if (json_decode($managementRank->rank) && in_array(5, json_decode($managementRank->rank))) selected @endif>Rank 5</option>
                                    <option value="6" @if (json_decode($managementRank->rank) && in_array(6, json_decode($managementRank->rank))) selected @endif>Rank 6</option>
                                    <option value="7" @if (json_decode($managementRank->rank) && in_array(7, json_decode($managementRank->rank))) selected @endif>Rank 7</option>
                                    <option value="8" @if (json_decode($managementRank->rank) && in_array(8, json_decode($managementRank->rank))) selected @endif>Rank 8</option>
                                    <option value="9" @if (json_decode($managementRank->rank) && in_array(9, json_decode($managementRank->rank))) selected @endif>Rank 9</option>
                                    <option value="10" @if (json_decode($managementRank->rank) && in_array(10, json_decode($managementRank->rank))) selected @endif>Rank 10
                                    </option>
                                </select>
                            </x-forms.input-group>
                        </div>
                    </div>

                    <x-form-actions>
                        <button type="submit" class="mr-3 btn-primary rounded f-14 p-2">
                            <i class="fa fa-check mr-1"></i>@lang('app.save')
                        </button>
                        <x-forms.button-cancel :link="route('management-ranks.index')" class="border-0">@lang('app.cancel')
                        </x-forms.button-cancel>
                    </x-form-actions>
                </div>
            </x-form>
        </div>
    </div>
@endsection
