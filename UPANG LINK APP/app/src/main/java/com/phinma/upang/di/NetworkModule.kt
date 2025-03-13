package com.phinma.upang.di

import com.google.gson.Gson
import com.google.gson.GsonBuilder
import com.google.gson.TypeAdapter
import com.google.gson.reflect.TypeToken
import com.google.gson.stream.JsonReader
import com.google.gson.stream.JsonWriter
import com.phinma.upang.BuildConfig
import com.phinma.upang.data.api.AuthApi
import com.phinma.upang.data.api.RequestApi
import com.phinma.upang.data.api.NotificationApi
import com.phinma.upang.data.api.interceptor.AuthInterceptor
import com.phinma.upang.data.model.ApiResponse
import com.phinma.upang.data.api.RequestService
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object NetworkModule {

    @Provides
    @Singleton
    fun provideGson(): Gson {
        return GsonBuilder()
            .setDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'")
            .setLenient()
            .registerTypeAdapter(object : TypeToken<ApiResponse<*>>() {}.type, object : TypeAdapter<ApiResponse<*>>() {
                override fun write(out: JsonWriter, value: ApiResponse<*>?) {
                    if (value == null) {
                        out.nullValue()
                        return
                    }
                    val gson = Gson()
                    out.beginObject()
                    out.name("status")
                    out.value(value.status)
                    out.name("message")
                    out.value(value.message)
                    out.name("data")
                    gson.toJson(value.data, value.data?.javaClass, out)
                    out.name("error_type")
                    out.value(value.error_type)
                    out.name("code")
                    value.code?.let { out.value(it) } ?: out.nullValue()
                    out.endObject()
                }

                override fun read(reader: JsonReader): ApiResponse<*> {
                    try {
                        val gson = Gson()
                        val jsonObject = gson.fromJson<Map<String, Any>>(reader, object : TypeToken<Map<String, Any>>() {}.type)
                        
                        return ApiResponse(
                            status = jsonObject["status"] as? String ?: "error",
                            message = jsonObject["message"] as? String,
                            data = jsonObject["data"],
                            error_type = jsonObject["error_type"] as? String,
                            code = (jsonObject["code"] as? Double)?.toInt()
                        )
                    } catch (e: Exception) {
                        return ApiResponse(
                            status = "error",
                            message = "Server returned invalid response",
                            data = null,
                            error_type = "MALFORMED_RESPONSE",
                            code = 500
                        )
                    }
                }
            })
            .create()
    }

    @Provides
    @Singleton
    fun provideOkHttpClient(authInterceptor: AuthInterceptor): OkHttpClient {
        return OkHttpClient.Builder()
            .addInterceptor(authInterceptor)
            .addInterceptor(HttpLoggingInterceptor().apply {
                level = if (BuildConfig.DEBUG) {
                    HttpLoggingInterceptor.Level.BODY
                } else {
                    HttpLoggingInterceptor.Level.NONE
                }
            })
            .connectTimeout(60, TimeUnit.SECONDS)
            .readTimeout(60, TimeUnit.SECONDS)
            .writeTimeout(60, TimeUnit.SECONDS)
            .build()
    }

    @Provides
    @Singleton
    fun provideRetrofit(okHttpClient: OkHttpClient, gson: Gson): Retrofit {
        return Retrofit.Builder()
            .baseUrl(BuildConfig.API_BASE_URL)
            .client(okHttpClient)
            .addConverterFactory(GsonConverterFactory.create(gson))
            .build()
    }

    @Provides
    @Singleton
    fun provideAuthApi(retrofit: Retrofit): AuthApi {
        return retrofit.create(AuthApi::class.java)
    }

    @Provides
    @Singleton
    fun provideRequestApi(retrofit: Retrofit): RequestApi {
        return retrofit.create(RequestApi::class.java)
    }

    @Provides
    @Singleton
    fun provideNotificationApi(retrofit: Retrofit): NotificationApi {
        return retrofit.create(NotificationApi::class.java)
    }

    @Provides
    @Singleton
    fun provideRequestService(retrofit: Retrofit): RequestService {
        return retrofit.create(RequestService::class.java)
    }
} 