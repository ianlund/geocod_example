drop schema public cascade;
create schema public;

create extension pgcrypto;

create table geography_geocoding_services (
	id text primary key,
	name text not null
);

insert into geography_geocoding_services (id, name) values ('geocod', 'Geocod');

create table geography_geocoding_services_requests (
	id uuid primary key default gen_random_uuid(),
	service_id text not null references geography_geocoding_services(id),
	requests_when date not null default now(),
	requests_count integer not null default 0
);
alter table geography_geocoding_services_requests add unique (service_id, requests_when);

create table addresses (
	id serial primary key,
	
	address_street text not null,
	address_locality text not null,
	address_division text not null,
	address_postcode text not null,
	address_country text not null,

	geocode_service_id text references geography_geocoding_services(id),
	geocode_completed_when timestamptz,
	geocode_data jsonb,
	geocode_accuracy numeric,
	geocode_latitude numeric,
	geocode_longitude numeric,

	geocode_address_full text,
	geocode_address_street text,
	geocode_address_locality text,
	geocode_address_division text,
	geocode_address_postcode text,
	geocode_address_country text
);

insert into addresses (address_street, address_locality, address_division, address_postcode, address_country) values
	('1600 pennsylvania ave nw', 'washington', 'dc', '20500', 'usa'),
	('first st se', 'washington', 'dc', '20004', 'usa'),
	('1 apple park way', 'cupertina', 'ca', '95014', 'usa'),
	('1 microsoft way', 'redmond', 'wa', '98052', 'usa'),
	('1109 n highland st', 'arlington', 'va', '22201', 'usa');
	

create function trigger_addresses_increment_geocode_request_count()
returns trigger language plpgsql as $$
begin
	if (
		(tg_op = 'INSERT' and new.geocode_completed_when is not null) or
		(tg_op = 'UPDATE' and new.geocode_completed_when is distinct from old.geocode_completed_when)
	) then
		insert into geography_geocoding_services_requests (service_id, requests_when) values
			(new.geocode_service_id, now())
		on conflict (service_id, requests_when) do update set requests_count = excluded.requests_count + 1;
	end if;
	
	return new;
end;
$$;

create trigger increment_request_count after insert or update on addresses for each row
	execute procedure trigger_addresses_increment_geocode_request_count();