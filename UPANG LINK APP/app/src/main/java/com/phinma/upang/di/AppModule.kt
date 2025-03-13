package com.phinma.upang.di

import com.phinma.upang.data.auth.TokenProvider
import com.phinma.upang.data.auth.TokenStorage
import dagger.Binds
import dagger.Module
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
abstract class AppModule {
    @Binds
    @Singleton
    abstract fun bindTokenProvider(tokenStorage: TokenStorage): TokenProvider
} 