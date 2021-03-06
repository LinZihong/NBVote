<?php

namespace App\Events;

//use Illuminate\Broadcasting\Channel;
//use Illuminate\Queue\SerializesModels;
//use Illuminate\Broadcasting\PrivateChannel;
//use Illuminate\Broadcasting\PresenceChannel;
//use Illuminate\Foundation\Events\Dispatchable;
//use Illuminate\Broadcasting\InteractsWithSockets;
//use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UpdateModelIPAddress extends Event
{
//    use Dispatchable, InteractsWithSockets, SerializesModels;

	/**
	 * Create a new event instance.
	 *
	 * @return void
	 */
	public function __construct($type,$id,$description,$ipAddress)
	{
		//
		$this->type = $type;
		$this->id = $id;
		$this->description = $description;
		$this->ip_address = $ipAddress;
	}

	/**
	 * Get the channels the event should broadcast on.
	 *
	 * @return Channel|array
	 */
	public function broadcastOn()
	{
		return new PrivateChannel('channel-name');
	}
}
