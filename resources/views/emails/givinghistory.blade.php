<!DOCTYPE html>
<div style="margin:0px;padding:20px; overflow: visible; width: 100% !important; font-size: 18px; font-family: Montserrat">
  <div>
    <p style="line-height:1.5;margin:0 0 17px;text-align:left!important; font-weight: bold" align="left">Giving History {{$year}}</p>
    <p style="line-height:1.5;margin:0 0 17px;text-align:left!important" align="left">
    <div style="font-weight: bold">DBA: </div> {{$organizationDba}} </p>
    <p style="line-height:1.5;margin:0 0 17px;text-align:left!important" align="left">
    <div style="font-weight: bold">Organization Legal Name:</div> {{$organizationName}}</p>
    <p style="line-height:1.5;margin:0 0 17px;text-align:left!important" align="left">
    <div style="font-weight: bold">EIN: </div> {{$ein}}</p>
  </div>


  @foreach ($donationsByUser as $user)
  <div style="line-height:1.5;margin-top: 16px; margin-bottom: 8px;  text-align:left!important; font-weight: bold; " align="left">{{$user['email']}}</div>
  <table style="width: 100%; padding-left: 16px">
    <tr>
      <th>Amount</th>
      <th>Date</th>
      <th>Tip</th>
      <th>Payment Method</th>
      <th>Frequency </th>
      <th>Designation </th>
    </tr>
    @foreach ($user['donations'] as $donation)



    <tr>
      <td>${{$donation['amount'] / 100 }}</td>
      <td> {{$donation['created_at']->format('m/d/Y')}}</td>
      <td> ${{$donation['tip'] ?? 0}}</td>
      <td> {{ucwords($donation['source_type'])}} *{{optional($donation['source'])->last_four}}</td>
      <td> {{ucwords(App\Models\ScheduledDonation::DONATION_FREQUENCY_MAP[$donation['scheduledDonation'] ? $donation['scheduledDonation']->frequency : 0]) ?? 'Once' }} </td>
      <td> {{ucwords($donation['fund_id'] ? $donation['fund']->name : 'General Fund')  }} </td>
    </tr>
    @endforeach
  </table>
  @endforeach

</div>


<style>
  td,
  th {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 8px;
  }

  @font-face {
    font-family: 'Montserrate';
    font-style: normal;
    font-weight: normal;
    src: url("https://fonts.googleapis.com/css?family=Montserrat:100,300,400,500,700,900") format('truetype');
  }
</style>
